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
 * registradas en BD para el tipoFormacion de la ficha indicada.
 *
 * Flujo:
 *  1. Recibe el archivo Excel + idFicha
 *  2. Delega la lectura del Excel al JuiciosEvaluativosService (ya existente)
 *  3. Carga las competencias de BD asociadas al tipoFormacion de esa ficha
 *  4. Cruza ambas fuentes:
 *     - Competencia en BD y en Excel -> usa el porcentaje calculado del Excel
 *     - Competencia en BD pero NO en Excel -> 0% (nadie fue evaluado)
 *     - Competencia en Excel pero NO en BD -> se ignora (no pertenece al programa)
 *  5. Filtra las que tienen < 80% de aprobacion
 *  6. Genera y guarda un archivo .txt en storage con el resultado
 *  7. Retorna el path del archivo + el listado como array (para JSON)
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

    /**
     * Genera el reporte de competencias pendientes para una ficha.
     *
     * @param  UploadedFile $archivo   Excel de juicios evaluativos
     * @param  int          $idFicha   ID de la ficha
     * @return array
     */
    public function generarReporte(UploadedFile $archivo, int $idFicha): array
    {
        // Paso 1: Cargar la ficha con su cadena de relaciones
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

        // Paso 2: Cargar competencias de BD para ese tipoFormacion
        $competenciasDeBD = CompetenciaModel::with('resultados')
            ->where('idTipoFormacion', $tipoFormacion->idTipoFormacion)
            ->get();

        if ($competenciasDeBD->isEmpty()) {
            return $this->respuestaError(
                "No hay competencias registradas para el tipo de formacion '{$tipoFormacion->nombreTipoFormacion}'."
            );
        }

        // Paso 3: Analizar el Excel con el service existente
        $analisisDelExcel = $this->juiciosService->analizar($archivo);

        // Indexar las competencias del Excel por codigo para busqueda O(1)
        $competenciasDelExcel = collect($analisisDelExcel['competencias'])
            ->keyBy('codigo');

        // Paso 4: Cruzar BD vs Excel
        $pendientes = [];
        $cubiertas  = [];

        foreach ($competenciasDeBD as $competenciaBD) {
            $codigoBD = trim($competenciaBD->codigo);

            $datosDelExcel = $competenciasDelExcel->get($codigoBD);

            if ($datosDelExcel) {
                // CASO A: esta en BD y en Excel -> usar porcentaje del Excel
                $porcentajeMinimo = $this->calcularPorcentajeMinimoDeResultados(
                    $datosDelExcel['resultados'] ?? []
                );

                $entrada = [
                    'codigo'     => $codigoBD,
                    'nombre'     => $competenciaBD->nombreCompetencia,
                    'tipo'       => $competenciaBD->tipo,
                    'porcentaje' => $porcentajeMinimo,
                    'fuente'     => 'excel',
                    'resultados' => $datosDelExcel['resultados'] ?? [],
                ];
            } else {
                // CASO B: esta en BD pero NO en Excel -> 0%
                $entrada = [
                    'codigo'     => $codigoBD,
                    'nombre'     => $competenciaBD->nombreCompetencia,
                    'tipo'       => $competenciaBD->tipo,
                    'porcentaje' => 0.0,
                    'fuente'     => 'sin_datos_en_excel',
                    'resultados' => [],
                ];
            }

            if ($entrada['porcentaje'] < self::UMBRAL_APROBACION) {
                $pendientes[] = $entrada;
            } else {
                $cubiertas[] = $entrada;
            }
        }

        // Ordenar pendientes de menor a mayor porcentaje (las mas urgentes primero)
        usort($pendientes, fn($a, $b) => $a['porcentaje'] <=> $b['porcentaje']);

        // Paso 5: Generar el archivo .txt y guardarlo en storage
        // Se pasan $programa->nombre y $tipoFormacion->nombreTipoFormacion como strings
        // para evitar acceder a relaciones Eloquent dentro del metodo privado
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

        // Paso 6: Retornar el resultado completo
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
    //  METODOS PRIVADOS
    // =========================================================================

    /**
     * Calcula el porcentaje MINIMO de aprobacion entre todos los resultados
     * de una competencia.
     *
     * Una competencia esta "cubierta" solo si TODOS sus resultados superan
     * el umbral. Por eso usamos el minimo: si uno falla, la competencia falla.
     */
    private function calcularPorcentajeMinimoDeResultados(array $resultados): float
    {
        if (empty($resultados)) {
            return 0.0;
        }

        $porcentajes = array_column($resultados, 'porcentaje_aprobacion');

        return empty($porcentajes) ? 0.0 : (float) min($porcentajes);
    }

    /**
     * Construye el contenido del archivo .txt de salida.
     *
     * Recibe $nombrePrograma y $nombreTipoFormacion como strings independientes
     * para no depender de relaciones Eloquent dentro del metodo.
     *
     * Formato de salida:
     *   ============================================================
     *   REPORTE DE COMPETENCIAS PENDIENTES DE EVALUACION
     *   ============================================================
     *   Ficha           : 3171062
     *   Programa        : Analisis y Desarrollo de Software
     *   Tipo Formacion  : Tecnologo
     *   Total Aprendices: 29
     *   Umbral          : 80%
     *   Generado        : 2026-03-19 14:30:00
     *   ============================================================
     *
     *   COMPETENCIAS QUE NECESITAN EVALUACION (3)
     *   ------------------------------------------------------------
     *   [PENDIENTE] 37371 - Herramientas informaticas - 0.00%
     *   [PENDIENTE] 37714 - Ingles - 45.00%
     *
     *   COMPETENCIAS CUBIERTAS (2)
     *   ------------------------------------------------------------
     *   [CUBIERTO]  37800 - Habitos saludables - 100.00%
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

        // Encabezado
        $lineas[] = str_repeat('=', 60);
        $lineas[] = 'REPORTE DE COMPETENCIAS PENDIENTES DE EVALUACION';
        $lineas[] = str_repeat('=', 60);
        $lineas[] = "Ficha           : {$ficha->codigoFicha}";
        $lineas[] = "Programa        : {$nombrePrograma}";
        $lineas[] = "Tipo Formacion  : {$nombreTipoFormacion}";
        $lineas[] = "Total Aprendices: {$totalAprendices}";
        $lineas[] = "Umbral          : " . self::UMBRAL_APROBACION . "%";
        $lineas[] = "Generado        : " . now()->format('Y-m-d H:i:s');
        $lineas[] = str_repeat('=', 60);
        $lineas[] = '';

        // Competencias pendientes
        $cantPendientes = count($pendientes);
        $lineas[] = "COMPETENCIAS QUE NECESITAN EVALUACION ({$cantPendientes})";
        $lineas[] = str_repeat('-', 60);

        if (empty($pendientes)) {
            $lineas[] = '  (Ninguna - todas las competencias superaron el umbral)';
        } else {
            foreach ($pendientes as $comp) {
                $porcentaje = number_format($comp['porcentaje'], 2);
                $fuente     = $comp['fuente'] === 'sin_datos_en_excel'
                    ? ' [SIN DATOS EN EXCEL]'
                    : '';

                $lineas[] = "[PENDIENTE] {$comp['codigo']} - {$comp['nombre']} - {$porcentaje}%{$fuente}";

                // Detalle de resultados si los hay
                if (!empty($comp['resultados'])) {
                    foreach ($comp['resultados'] as $resultado) {
                        $pct    = number_format($resultado['porcentaje_aprobacion'], 2);
                        $estado = $resultado['necesita_horario'] ? 'PENDIENTE' : 'OK';
                        $lineas[] = "   [{$estado}] {$resultado['codigo']} - {$pct}%";
                    }
                }
            }
        }

        $lineas[] = '';

        // Competencias cubiertas
        $cantCubiertas = count($cubiertas);
        $lineas[] = "COMPETENCIAS CUBIERTAS ({$cantCubiertas})";
        $lineas[] = str_repeat('-', 60);

        if (empty($cubiertas)) {
            $lineas[] = '  (Ninguna supero el umbral)';
        } else {
            foreach ($cubiertas as $comp) {
                $porcentaje = number_format($comp['porcentaje'], 2);
                $lineas[] = "[CUBIERTO]  {$comp['codigo']} - {$comp['nombre']} - {$porcentaje}%";
            }
        }

        $lineas[] = '';
        $lineas[] = str_repeat('=', 60);

        return implode(PHP_EOL, $lineas);
    }

    /**
     * Genera un nombre de archivo unico para el reporte.
     * Formato: reporte_ficha_{codigoFicha}_{idFicha}_{timestamp}.txt
     */
    private function generarNombreArchivo(int $idFicha, string $codigoFicha): string
    {
        $timestamp    = now()->format('Ymd_His');
        $codigoLimpio = preg_replace('/[^a-zA-Z0-9_-]/', '_', $codigoFicha);

        return "reporte_ficha_{$codigoLimpio}_{$idFicha}_{$timestamp}.txt";
    }

    /**
     * Construye un array de respuesta de error uniforme.
     */
    private function respuestaError(string $mensaje): array
    {
        return [
            'ok'      => false,
            'mensaje' => $mensaje,
        ];
    }
}