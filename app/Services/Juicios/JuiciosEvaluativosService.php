<?php

namespace App\Services\Juicios;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * JuiciosEvaluativosService
 *
 * Lee el reporte de juicios evaluativos del SENA (Excel) en memoria,
 * calcula el porcentaje de aprobación por cada Resultado de Aprendizaje
 * y determina qué competencias aún necesitan programación de horario.
 *
 * IMPORTANTE: Este service NUNCA guarda el archivo ni sus datos en BD.
 * El archivo se lee, se procesa y se descarta. Solo retorna un array/JSON.
 *
 * Umbral: si el 80% o más de los aprendices tienen APROBADO un resultado,
 * ese resultado NO necesita más horas. Si algún resultado de la competencia
 * está por debajo del 80%, la competencia SÍ necesita horario.
 */
class JuiciosEvaluativosService
{
    // Umbral configurable — si en el futuro quieren cambiar el 80% es solo aquí
    private const UMBRAL_APROBACION = 80.0;

    /**
     * Punto de entrada principal.
     *
     * Recibe el archivo subido, lo lee en memoria con PhpSpreadsheet
     * (la misma librería que usa maatwebsite/excel por debajo),
     * lo procesa y retorna el análisis completo.
     *
     * $archivo  El archivo .xlsx subido por el usuario
     *           Análisis completo listo para retornar como JSON
     */
    public function analizar(UploadedFile $archivo): array
    {
        // ── Paso 1: Leer el Excel en memoria ──────────────────────────────────
        // IOFactory::load() carga el archivo en un objeto PhpSpreadsheet.
        // Usamos la ruta temporal del archivo subido — PHP la limpia solo después.
        $spreadsheet = IOFactory::load($archivo->getRealPath());
        $hoja= $spreadsheet->getActiveSheet();

        // toArray() convierte toda la hoja en un array PHP bidimensional.
        // El primer índice es la fila, el segundo es la columna (ambos desde 0).
        // null = valor de celda crudo, true = calcular fórmulas, false = no formatear
        $filas = $hoja->toArray(null, true, false, false);

        // ── Paso 2: Extraer la metadata del encabezado ────────────────────────
        // Las primeras 12 filas tienen info de la ficha, programa, fechas, etc.
        // Ej: fila[1] = ['Fecha del Reporte:', null, '16/03/2026', ...]
        $metadata = $this->extraerMetadata($filas);

        // ── Paso 3: Extraer las filas de datos reales ─────────────────────────
        // La fila 12 (índice 12) es el encabezado de columnas.
        // Los datos empiezan desde la fila 13 (índice 13).
        $filasDeAprendices = array_slice($filas, 13);

        // ── Paso 4: Contar aprobados/por evaluar por resultado ────────────────
        $conteos = $this->contarJuiciosPorResultado($filasDeAprendices);

        // ── Paso 5: Calcular porcentajes y clasificar ─────────────────────────
        $totalAprendices = $this->contarAprendicesUnicos($filasDeAprendices);
        $analisis        = $this->calcularAnalisis($conteos, $totalAprendices);

        // ── Paso 6: Armar la respuesta final ──────────────────────────────────
        return [
            'metadata'         => $metadata,
            'total_aprendices' => $totalAprendices,
            'umbral_usado'     => self::UMBRAL_APROBACION,
            'resumen' => [  
                'competencias_necesitan_horario'  => count(array_filter($analisis, fn($c) => $c['necesita_horario'])),
                'competencias_cubiertas'          => count(array_filter($analisis, fn($c) => !$c['necesita_horario'])),
            ],
            'competencias' => array_values($analisis),
        ];
    }

    // =========================================================================
    //  MÉTODOS PRIVADOS — cada uno hace UNA sola cosa
    // =========================================================================

    /**
     * Extrae la metadata de las primeras filas del reporte.
     *
     * El reporte del SENA tiene este formato en las primeras filas:
     *   Fila 1: ['Fecha del Reporte:', null, '16/03/2026']
     *   Fila 2: ['Ficha de Caracterización:', null, '3171062']
     *   etc.
     *
     * La clave está en la columna 0, el valor en la columna 2.
     */
    private function extraerMetadata(array $filas): array
    {
        $metadata = [];

        // Solo miramos las primeras 12 filas (el encabezado del reporte)
        foreach (array_slice($filas, 0, 12) as $fila) {
            $clave = trim((string)($fila[0] ?? ''));
            $valor = trim((string)($fila[2] ?? ''));

            if (!empty($clave) && !empty($valor)) {
                // Limpiamos los dos puntos del final de la clave
                // Ej: 'Ficha de Caracterización:' → 'Ficha de Caracterización'
                $metadata[rtrim($clave, ':')] = $valor;
            }
        }

        return $metadata;
    }

    /**
     * Recorre todas las filas de aprendices y agrupa los conteos por:
     * competencia → resultado → juicio (APROBADO / POR EVALUAR)
     *
     * Estructura del resultado:
     * [
     *   'codigo_competencia::nombre_competencia' => [
     *     'codigo_resultado::nombre_resultado' => [
     *       'aprobados'    => ['doc1', 'doc2', ...],  // documentos únicos
     *       'por_evaluar'  => ['doc3', ...],
     *     ]
     *   ]
     * ]
     *
     * Usamos conjuntos de documentos (no solo contadores) para contar
     * aprendices ÚNICOS — un aprendiz puede aparecer varias veces.
     */
    private function contarJuiciosPorResultado(array $filas): array
    {
        // Índices de columna según el Excel del SENA:
        // Col 1 = Tipo Doc, Col 2 = Número Documento, Col 3 = Nombre
        // Col 4 = Apellidos, Col 5 = Estado, Col 6 = Competencia
        // Col 7 = Resultado de Aprendizaje, Col 8 = Juicio de Evaluación
        $conteos = [];

        foreach ($filas as $fila) {
            $documento   = trim((string)($fila[1] ?? ''));
            $competencia = trim((string)($fila[5] ?? ''));
            $resultado   = trim((string)($fila[6] ?? ''));
            $juicio      = trim((string)($fila[7] ?? ''));

            // Saltamos filas incompletas o vacías
            if (empty($documento) || empty($competencia) || empty($resultado) || empty($juicio)) {
                continue;
            }

            // JUICIOS PRO EVALUACIO('APROBADO' Y 'POR EVALUAR')
            if (!in_array(strtoupper($juicio), ['APROBADO', 'POR EVALUAR'])) {
                continue;
            }

            // Se usa la competencia y resultado completos como claves
            // para preservar toda la información
            if (!isset($conteos[$competencia][$resultado])) {
                $conteos[$competencia][$resultado] = [
                    'aprobados'   => [],
                    'por_evaluar' => [],
                ];
            }

            // Guarda el documento para contar únicos
            // (array_unique después evita duplicados)
            if (strtoupper($juicio) === 'APROBADO') {
                $conteos[$competencia][$resultado]['aprobados'][$documento] = true;
            } else {
                $conteos[$competencia][$resultado]['por_evaluar'][$documento] = true;
            }
        }

        return $conteos;
    }

    /**
     * Cuenta cuántos aprendices únicos hay en el reporte.
     * Un aprendiz aparece en muchas filas (una por cada resultado),
     * así que contamos documentos únicos.
     */
    private function contarAprendicesUnicos(array $filas): int
    {
        $documentos = [];

        foreach ($filas as $fila) {
            $documento = trim((string)($fila[1] ?? ''));
            if (!empty($documento)) {
                $documentos[$documento] = true;
            }
        }

        return count($documentos);
    }

    /**
     * Con los conteos ya hechos, calcula porcentajes y decide
     * si cada competencia necesita horario o no.
     *
     * Una competencia NECESITA HORARIO si AL MENOS UNO de sus
     * resultados de aprendizaje tiene menos del 80% aprobado.
     *
     * Una competencia está CUBIERTA solo si TODOS sus resultados
     * tienen 80% o más de aprobación.
     */
    private function calcularAnalisis(array $conteos, int $totalAprendices): array
    {
        $analisis = [];

        foreach ($conteos as $competencia => $resultados) {
            // Extraer el código numérico de la competencia
            // Ej: '36180 - Enrique Low Murtra-Interactuar...' → '36180'
            $codigoCompetencia = $this->extraerCodigo($competencia);

            $resultadosAnalizados   = [];
            $algunoNecesitaHorario  = false;

            foreach ($resultados as $resultado => $juicios) {
                $cantAprobados  = count($juicios['aprobados']);
                $cantPorEvaluar = count($juicios['por_evaluar']);
                $total          = $cantAprobados + $cantPorEvaluar;

                // Porcentaje sobre el total de aprendices de la ficha
                // (no solo los que tienen juicio, porque algunos pueden estar POR EVALUAR)
                $porcentaje = $totalAprendices > 0
                    ? round(($cantAprobados / $totalAprendices) * 100, 2)
                    : 0;

                $necesitaHorario = $porcentaje < self::UMBRAL_APROBACION;

                if ($necesitaHorario) {
                    $algunoNecesitaHorario = true;
                }

                $codigoResultado = $this->extraerCodigo($resultado);

                $resultadosAnalizados[] = [
                    'codigo'           => $codigoResultado,
                    'nombre_completo'  => $resultado,
                    'aprobados'        => $cantAprobados,
                    'por_evaluar'      => $cantPorEvaluar,
                    'total_con_juicio' => $total,
                    'porcentaje_aprobacion' => $porcentaje,
                    'necesita_horario' => $necesitaHorario,
                    'estado'           => $necesitaHorario ? 'PENDIENTE' : 'CUBIERTO',
                ];
            }

            $analisis[$codigoCompetencia] = [
                'codigo'           => $codigoCompetencia,
                'nombre_completo'  => $competencia,
                'necesita_horario' => $algunoNecesitaHorario,
                'estado'           => $algunoNecesitaHorario ? 'PENDIENTE' : 'CUBIERTO',
                'resultados'       => $resultadosAnalizados,
            ];
        }

        return $analisis;
    }

    /**
     * Extrae el código numérico del inicio de un texto del SENA.
     *
     * El formato SENA siempre es: 'CODIGO - Descripción larga...'
     * Ej: '36180 - Enrique Low Murtra-Interactuar...' → '36180'
     * Ej: '593150 - 04  CONTRIBUIR CON...' → '593150'
     *
     * Si no encuentra un código, retorna el texto completo truncado.
     */
    private function extraerCodigo(string $texto): string
    {
        // Buscar un número al inicio del texto (antes del primer ' - ' o espacio)
        if (preg_match('/^(\d+)/', trim($texto), $matches)) {
            return $matches[1];
        }

        // Si no hay código numérico, retornar los primeros 40 caracteres
        return substr($texto, 0, 40);
    }
}