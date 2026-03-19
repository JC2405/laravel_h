<?php

namespace App\Services\Juicios;

use App\Models\FichaModel;
use App\Models\CompetenciaModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * ReporteCompetenciasService
 *
 * Cruza el análisis de juicios evaluativos (Excel) con las competencias
 * y resultados de aprendizaje registrados en BD para el tipoFormacion
 * de la ficha indicada.
 *
 * Flujo:
 *  1. Recibe el archivo Excel + idFicha
 *  2. Delega la lectura del Excel al JuiciosEvaluativosService
 *  3. Carga las competencias de BD (con sus resultados eager-loaded)
 *  4. Cruza a nivel de COMPETENCIA:
 *       BD + Excel  → usa porcentaje mínimo del Excel
 *       BD sin Excel → 0% (nadie fue evaluado)
 *       Excel sin BD → se ignora (no pertenece al programa)
 *  5. Cruza a nivel de RESULTADO dentro de cada competencia:
 *       BD + Excel y >= umbral → evaluado
 *       BD + Excel y <  umbral → pendiente
 *       BD sin Excel           → sin_datos (instructor no registró juicio)
 *  6. Genera y guarda un archivo .txt en storage
 *  7. Retorna el path + el análisis completo como array JSON-ready
 */
class ReporteCompetenciasService
{
    private const UMBRAL_APROBACION = 80.0;

    public function __construct(
        private JuiciosEvaluativosService $juiciosService
    ) {}

    // =========================================================================
    //  PUNTO DE ENTRADA
    // =========================================================================

    public function generarReporte(UploadedFile $archivo, int $idFicha): array
    {
        // ── Paso 1: cargar ficha con su cadena de relaciones ──────────────────
        $ficha = FichaModel::with(['programa.tipoFormacion'])->find($idFicha);

        if (!$ficha) {
            return $this->respuestaError("La ficha con ID {$idFicha} no existe.");
        }

        $programa = $ficha->programa;
        if (!$programa) {
            return $this->respuestaError("La ficha {$idFicha} no tiene un programa asociado.");
        }

        $tipoFormacion = $programa->tipoFormacion;
        if (!$tipoFormacion) {
            return $this->respuestaError("El programa '{$programa->nombre}' no tiene tipo de formacion.");
        }

        // ── Paso 2: cargar competencias de BD con sus resultados ──────────────
        // Eager-loading de 'resultados' para evitar N+1 al recorrer la colección
        $competenciasDeBD = CompetenciaModel::with('resultados')
            ->where('idTipoFormacion', $tipoFormacion->idTipoFormacion)
            ->get();

        if ($competenciasDeBD->isEmpty()) {
            return $this->respuestaError(
                "No hay competencias registradas para el tipo de formacion '{$tipoFormacion->nombreTipoFormacion}'."
            );
        }

        // ── Paso 3: analizar el Excel ─────────────────────────────────────────
        $analisisDelExcel = $this->juiciosService->analizar($archivo);

        // Indexar competencias del Excel por código extraído → O(1) en búsquedas
        // Ej: ['36180' => [...], '228186' => [...]]
        $competenciasDelExcel = collect($analisisDelExcel['competencias'])
            ->keyBy('codigo');

        // ── Paso 4 + 5: cruzar BD vs Excel a nivel competencia Y resultado ────
        $pendientes = [];
        $cubiertas  = [];

        foreach ($competenciasDeBD as $competenciaBD) {
            $codigoBD      = trim($competenciaBD->codigo);
            $datosDelExcel = $competenciasDelExcel->get($codigoBD);

            // Construir el índice de resultados del Excel para esta competencia
            // para poder buscar resultado.codigo en O(1)
            $resultadosDelExcelIndexados = $this->indexarResultadosDelExcel(
                $datosDelExcel['resultados'] ?? []
            );

            // Cruzar cada resultado de BD contra el índice del Excel
            $resultadosCruzados = $this->cruzarResultadosDeBD(
                $competenciaBD->resultados,
                $resultadosDelExcelIndexados,
                $analisisDelExcel['total_aprendices'] ?? 0
            );

            // Calcular el porcentaje mínimo considerando también los
            // resultados de BD que NO están en el Excel (esos son 0%)
            $porcentajeMinimo = $this->calcularPorcentajeMinimoConBD(
                $resultadosCruzados,
                $datosDelExcel
            );

            $entrada = [
                'codigo'              => $codigoBD,
                'nombre'              => $competenciaBD->nombreCompetencia,
                'tipo'                => $competenciaBD->tipo,
                'porcentaje'          => $porcentajeMinimo,
                'fuente'              => $datosDelExcel ? 'excel' : 'sin_datos_en_excel',
                // Resultados enriquecidos con el cruce BD <-> Excel
                'resultados'          => $resultadosCruzados,
                // Resumen rápido para el frontend
                'resumen_resultados'  => $this->calcularResumenResultados($resultadosCruzados),
            ];

            if ($entrada['porcentaje'] < self::UMBRAL_APROBACION) {
                $pendientes[] = $entrada;
            } else {
                $cubiertas[] = $entrada;
            }
        }

        // Ordenar pendientes de menor a mayor porcentaje (más urgentes primero)
        usort($pendientes, fn($a, $b) => $a['porcentaje'] <=> $b['porcentaje']);

        // ── Paso 6: generar el .txt y guardarlo en storage ────────────────────
        $contenidoTxt = $this->construirContenidoTxt(
            pendientes:          $pendientes,
            cubiertas:           $cubiertas,
            ficha:               $ficha,
            nombrePrograma:      $programa->nombre,
            nombreTipoFormacion: $tipoFormacion->nombreTipoFormacion,
            totalAprendices:     $analisisDelExcel['total_aprendices'] ?? 0
        );

        $nombreArchivo = $this->generarNombreArchivo($idFicha, $ficha->codigoFicha);
        $pathRelativo  = "reportes/{$nombreArchivo}";

        Storage::put($pathRelativo, $contenidoTxt);

        // ── Paso 7: retornar el resultado completo ────────────────────────────
        return [
            'ok'                      => true,
            'path_txt'                => $pathRelativo,
            'ficha'                   => $ficha->codigoFicha,
            'programa'                => $programa->nombre,
            'tipo_formacion'          => $tipoFormacion->nombreTipoFormacion,
            'total_aprendices'        => $analisisDelExcel['total_aprendices'] ?? 0,
            'umbral_usado'            => self::UMBRAL_APROBACION,
            'total_competencias_bd'   => $competenciasDeBD->count(),
            'total_pendientes'        => count($pendientes),
            'total_cubiertas'         => count($cubiertas),
            'competencias_pendientes' => $pendientes,
            'competencias_cubiertas'  => $cubiertas,
        ];
    }

    // =========================================================================
    //  CRUCE DE RESULTADOS  (nuevo núcleo de la solución)
    // =========================================================================

    /**
     * Construye un índice de búsqueda O(1) a partir de los resultados del Excel.
     *
     * Los resultados del Excel vienen como texto libre:
     *   "593150 - 04  CONTRIBUIR CON EL DESARROLLO..."
     *
     * Aplicamos el mismo extractor de código que usa JuiciosEvaluativosService
     * para obtener solo la parte numérica inicial y usarla como clave.
     *
     * Resultado:
     *   ['593150' => ['codigo' => '593150', 'porcentaje_aprobacion' => 72.4, ...]]
     *
     * @param  array $resultadosDelExcel  Array de resultados como viene del JuiciosService
     * @return array                      Indexado por código extraído
     */
    private function indexarResultadosDelExcel(array $resultadosDelExcel): array
    {
        $indice = [];

        foreach ($resultadosDelExcel as $resultado) {
            // El campo 'codigo' ya viene extraído por JuiciosEvaluativosService
            // (aplica el mismo regex /^(\d+)/ sobre el texto libre)
            $codigoExtraido = trim($resultado['codigo'] ?? '');

            if (!empty($codigoExtraido)) {
                // Si hay duplicados (edge case) conservar el de mayor porcentaje
                if (!isset($indice[$codigoExtraido]) ||
                    ($resultado['porcentaje_aprobacion'] ?? 0) > ($indice[$codigoExtraido]['porcentaje_aprobacion'] ?? 0)
                ) {
                    $indice[$codigoExtraido] = $resultado;
                }
            }
        }

        return $indice;
    }

    /**
     * Cruza los resultados de la BD de una competencia contra el índice del Excel.
     *
     * Para cada ResultadoModel de la BD determina uno de tres estados:
     *
     *  'evaluado'  → existe en el Excel Y el porcentaje >= umbral
     *  'pendiente' → existe en el Excel Y el porcentaje <  umbral
     *  'sin_datos' → NO existe en el Excel (el instructor nunca registró juicio)
     *
     * @param  \Illuminate\Database\Eloquent\Collection $resultadosDeBD
     *         Relación eager-loaded de ResultadoModel desde la BD
     * @param  array $indiceExcel
     *         Resultado de indexarResultadosDelExcel()
     * @param  int   $totalAprendices
     *         Total de aprendices únicos del reporte (para porcentaje relativo)
     * @return array  Lista de resultados enriquecidos lista para JSON
     */
    private function cruzarResultadosDeBD(
        $resultadosDeBD,
        array $indiceExcel,
        int $totalAprendices
    ): array {
        $resultadosCruzados = [];

        foreach ($resultadosDeBD as $resultadoBD) {
            $codigoBD = trim($resultadoBD->codigo);

            if (isset($indiceExcel[$codigoBD])) {
                // ── El resultado existe en el Excel ───────────────────────────
                $datosExcel  = $indiceExcel[$codigoBD];
                $porcentaje  = (float) ($datosExcel['porcentaje_aprobacion'] ?? 0.0);
                $necesita    = $porcentaje < self::UMBRAL_APROBACION;

                $resultadosCruzados[] = [
                    'idResultado'           => $resultadoBD->idResultado,
                    'codigo'                => $codigoBD,
                    'nombre'                => $resultadoBD->nombre,
                    'fuente'                => 'excel',
                    'estado'                => $necesita ? 'pendiente' : 'evaluado',
                    'porcentaje_aprobacion' => $porcentaje,
                    'aprobados'             => $datosExcel['aprobados']    ?? 0,
                    'por_evaluar'           => $datosExcel['por_evaluar']  ?? 0,
                    'total_con_juicio'      => $datosExcel['total_con_juicio'] ?? 0,
                    'nombre_completo_excel' => $datosExcel['nombre_completo'] ?? null,
                ];
            } else {
                // ── El resultado NO está en el Excel ──────────────────────────
                // El instructor no registró ningún juicio para este resultado.
                // Esto es información crítica: significa que 0% está aprobado.
                $resultadosCruzados[] = [
                    'idResultado'           => $resultadoBD->idResultado,
                    'codigo'                => $codigoBD,
                    'nombre'                => $resultadoBD->nombre,
                    'fuente'                => 'sin_datos',
                    'estado'                => 'sin_datos',
                    'porcentaje_aprobacion' => 0.0,
                    'aprobados'             => 0,
                    'por_evaluar'           => 0,
                    'total_con_juicio'      => 0,
                    'nombre_completo_excel' => null,
                ];
            }
        }

        // Ordenar: primero los sin_datos (más críticos), luego pendientes, luego evaluados
        usort($resultadosCruzados, function ($a, $b) {
            $orden = ['sin_datos' => 0, 'pendiente' => 1, 'evaluado' => 2];
            $ordenA = $orden[$a['estado']] ?? 9;
            $ordenB = $orden[$b['estado']] ?? 9;

            // Si mismo estado, ordenar por porcentaje ascendente
            if ($ordenA === $ordenB) {
                return $a['porcentaje_aprobacion'] <=> $b['porcentaje_aprobacion'];
            }

            return $ordenA <=> $ordenB;
        });

        return $resultadosCruzados;
    }

    /**
     * Calcula el porcentaje mínimo de aprobación de una competencia
     * considerando TODOS sus resultados de BD (incluyendo los sin_datos = 0%).
     *
     * Si la competencia no está en el Excel en absoluto, retorna 0.
     * Si tiene resultados en BD que no aparecen en el Excel, esos cuentan como 0%,
     * lo que arrastra el mínimo hacia abajo automáticamente.
     *
     * @param  array      $resultadosCruzados  Resultado de cruzarResultadosDeBD()
     * @param  array|null $datosDelExcel        Datos de la competencia del Excel (puede ser null)
     * @return float
     */
    private function calcularPorcentajeMinimoConBD(
        array $resultadosCruzados,
        ?array $datosDelExcel
    ): float {
        // Si la competencia no está en el Excel en absoluto → 0%
        if ($datosDelExcel === null) {
            return 0.0;
        }

        // Si no hay resultados en BD → usar el mínimo del Excel directamente
        if (empty($resultadosCruzados)) {
            $porcentajes = array_column($datosDelExcel['resultados'] ?? [], 'porcentaje_aprobacion');
            return empty($porcentajes) ? 0.0 : (float) min($porcentajes);
        }

        // Con resultados de BD: el mínimo considera también los sin_datos (0%)
        // lo que da una imagen más precisa de la realidad
        $porcentajes = array_column($resultadosCruzados, 'porcentaje_aprobacion');

        return empty($porcentajes) ? 0.0 : (float) min($porcentajes);
    }

    /**
     * Genera un resumen rápido de los resultados de una competencia.
     *
     * Útil para que el frontend muestre badges de estado sin
     * tener que recorrer el array completo de resultados.
     *
     * @param  array $resultadosCruzados
     * @return array  ['total' => 5, 'evaluados' => 2, 'pendientes' => 1, 'sin_datos' => 2]
     */
    private function calcularResumenResultados(array $resultadosCruzados): array
    {
        $resumen = [
            'total'      => count($resultadosCruzados),
            'evaluados'  => 0,
            'pendientes' => 0,
            'sin_datos'  => 0,
        ];

        foreach ($resultadosCruzados as $resultado) {
            match ($resultado['estado']) {
                'evaluado'  => $resumen['evaluados']++,
                'pendiente' => $resumen['pendientes']++,
                'sin_datos' => $resumen['sin_datos']++,
                default     => null,
            };
        }

        return $resumen;
    }

    // =========================================================================
    //  CÁLCULOS AUXILIARES
    // =========================================================================

    /**
     * Calcula el porcentaje MÍNIMO entre todos los resultados del Excel
     * para una competencia.
     *
     * Se mantiene por compatibilidad, pero el método principal ahora es
     * calcularPorcentajeMinimoConBD() que también considera los sin_datos.
     *
     * @deprecated Usar calcularPorcentajeMinimoConBD() en su lugar
     */
    private function calcularPorcentajeMinimoDeResultados(array $resultados): float
    {
        if (empty($resultados)) {
            return 0.0;
        }

        $porcentajes = array_column($resultados, 'porcentaje_aprobacion');

        return empty($porcentajes) ? 0.0 : (float) min($porcentajes);
    }

    // =========================================================================
    //  GENERACIÓN DEL ARCHIVO .TXT
    // =========================================================================

    /**
     * Construye el contenido del reporte .txt enriquecido con el detalle
     * de resultados (evaluado / pendiente / sin_datos).
     */
    private function construirContenidoTxt(
        array      $pendientes,
        array      $cubiertas,
        FichaModel $ficha,
        string     $nombrePrograma,
        string     $nombreTipoFormacion,
        int        $totalAprendices
    ): string {
        $lineas = [];

        // ── Encabezado ────────────────────────────────────────────────────────
        $lineas[] = str_repeat('=', 65);
        $lineas[] = 'REPORTE DE COMPETENCIAS PENDIENTES DE EVALUACION';
        $lineas[] = str_repeat('=', 65);
        $lineas[] = "Ficha           : {$ficha->codigoFicha}";
        $lineas[] = "Programa        : {$nombrePrograma}";
        $lineas[] = "Tipo Formacion  : {$nombreTipoFormacion}";
        $lineas[] = "Total Aprendices: {$totalAprendices}";
        $lineas[] = "Umbral          : " . self::UMBRAL_APROBACION . "%";
        $lineas[] = "Generado        : " . now()->format('Y-m-d H:i:s');
        $lineas[] = str_repeat('=', 65);
        $lineas[] = '';

        // ── Competencias pendientes ───────────────────────────────────────────
        $cantPendientes = count($pendientes);
        $lineas[] = "COMPETENCIAS QUE NECESITAN EVALUACION ({$cantPendientes})";
        $lineas[] = str_repeat('-', 65);

        if (empty($pendientes)) {
            $lineas[] = '  (Ninguna - todas las competencias superaron el umbral)';
        } else {
            foreach ($pendientes as $comp) {
                $porcentaje = number_format($comp['porcentaje'], 2);
                $etiquetaFuente = $comp['fuente'] === 'sin_datos_en_excel'
                    ? ' [SIN DATOS EN EXCEL]'
                    : '';

                $lineas[] = "[PENDIENTE] {$comp['codigo']} - {$comp['nombre']} - {$porcentaje}%{$etiquetaFuente}";

                // Resumen de resultados
                $resumen  = $comp['resumen_resultados'];
                $lineas[] = "   Resultados BD: {$resumen['total']} total | "
                          . "{$resumen['evaluados']} evaluados | "
                          . "{$resumen['pendientes']} pendientes | "
                          . "{$resumen['sin_datos']} sin datos";

                // Detalle por resultado
                foreach ($comp['resultados'] as $resultado) {
                    $pct    = number_format($resultado['porcentaje_aprobacion'], 2);
                    $estado = match ($resultado['estado']) {
                        'evaluado'  => 'OK       ',
                        'pendiente' => 'PENDIENTE',
                        'sin_datos' => 'SIN DATOS',
                        default     => '?????????',
                    };
                    $lineas[] = "   [{$estado}] {$resultado['codigo']} - {$resultado['nombre']} - {$pct}%";
                }

                $lineas[] = '';
            }
        }

        // ── Competencias cubiertas ────────────────────────────────────────────
        $cantCubiertas = count($cubiertas);
        $lineas[] = "COMPETENCIAS CUBIERTAS ({$cantCubiertas})";
        $lineas[] = str_repeat('-', 65);

        if (empty($cubiertas)) {
            $lineas[] = '  (Ninguna supero el umbral)';
        } else {
            foreach ($cubiertas as $comp) {
                $porcentaje = number_format($comp['porcentaje'], 2);
                $lineas[] = "[CUBIERTO]  {$comp['codigo']} - {$comp['nombre']} - {$porcentaje}%";

                // Mostrar solo los resultados que quedaron en estado evaluado
                foreach ($comp['resultados'] as $resultado) {
                    $pct = number_format($resultado['porcentaje_aprobacion'], 2);
                    $lineas[] = "   [OK      ] {$resultado['codigo']} - {$resultado['nombre']} - {$pct}%";
                }

                $lineas[] = '';
            }
        }

        $lineas[] = str_repeat('=', 65);

        return implode(PHP_EOL, $lineas);
    }

    // =========================================================================
    //  HELPERS
    // =========================================================================

    private function generarNombreArchivo(int $idFicha, string $codigoFicha): string
    {
        $timestamp    = now()->format('Ymd_His');
        $codigoLimpio = preg_replace('/[^a-zA-Z0-9_-]/', '_', $codigoFicha);

        return "reporte_ficha_{$codigoLimpio}_{$idFicha}_{$timestamp}.txt";
    }

    private function respuestaError(string $mensaje): array
    {
        return [
            'ok'      => false,
            'mensaje' => $mensaje,
        ];
    }
}