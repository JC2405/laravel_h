<?php

namespace App\Http\Controllers;

use App\Services\Juicios\ReporteCompetenciasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * ReporteController
 *
 * Expone los endpoints relacionados con la generación de reportes
 * de competencias pendientes de evaluación.
 *
 * RUTAS a agregar en api.php:
 *
 *   // Genera el reporte y lo guarda en storage
 *   Route::post('reportes/competencias-pendientes', [ReporteController::class, 'generarReporteCompetencias']);
 *
 *   // Descarga un reporte ya generado por su nombre de archivo
 *   Route::get('reportes/descargar/{nombre}', [ReporteController::class, 'descargarReporte']);
 */
class ReporteController extends Controller
{
    public function __construct(
        protected ReporteCompetenciasService $service
    ) {}

    // =========================================================================
    //  GENERAR REPORTE
    // =========================================================================

    /**
     * POST /api/reportes/competencias-pendientes
     *
     * Recibe el Excel de juicios evaluativos y el ID de ficha,
     * cruza con las competencias de BD, genera el análisis y
     * guarda el archivo .txt en storage.
     *
     * Body (multipart/form-data):
     *   archivo:   [ReporteJuiciosEvaluativos.xlsx]
     *   id_ficha:  123  (ID numérico de la ficha en la tabla ficha)
     *
     * Respuesta exitosa (200):
     * {
     *   "ok": true,
     *   "path_txt": "reportes/reporte_ficha_3171062_5_20260319_143000.txt",
     *   "ficha": "3171062",
     *   "programa": "Análisis y Desarrollo de Software",
     *   "tipo_formacion": "Tecnólogo",
     *   "total_aprendices": 29,
     *   "umbral_usado": 80,
     *   "total_competencias_bd": 5,
     *   "total_pendientes": 3,
     *   "total_cubiertas": 2,
     *   "competencias_pendientes": [ ... ],
     *   "competencias_cubiertas": [ ... ]
     * }
     */
    public function generarReporteCompetencias(Request $request)
    {
        // ── Validación de la petición ─────────────────────────────────────────
        $request->validate([
            'archivo'  => 'required|file|mimes:xlsx,xls|max:10240',
            'id_ficha' => 'required|integer|exists:ficha,idFicha',
        ], [
            'archivo.required'  => 'Debes subir el archivo de juicios evaluativos.',
            'archivo.mimes'     => 'El archivo debe ser Excel (.xlsx o .xls).',
            'archivo.max'       => 'El archivo no puede superar los 10MB.',
            'id_ficha.required' => 'Debes indicar el ID de la ficha.',
            'id_ficha.exists'   => 'La ficha indicada no existe en el sistema.',
        ]);

        try {
            $resultado = $this->service->generarReporte(
                archivo:  $request->file('archivo'),
                idFicha:  (int) $request->input('id_ficha')
            );

            // Si el service retornó un error de negocio
            if (!$resultado['ok']) {
                return response()->json([
                    'message' => $resultado['mensaje'],
                ], 422);
            }

            return response()->json($resultado, 200);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al procesar el archivo: ' . $e->getMessage(),
            ], 422);
        }
    }

    // =========================================================================
    //  DESCARGAR REPORTE YA GENERADO
    // =========================================================================

    /**
     * GET /api/reportes/descargar/{nombre}
     *
     * Descarga un archivo .txt de reporte previamente generado.
     *
     * El parámetro {nombre} es el nombre del archivo retornado en path_txt,
     * pero solo la parte del nombre (sin el prefijo "reportes/").
     *
     * Ejemplo:
     *   GET /api/reportes/descargar/reporte_ficha_3171062_5_20260319_143000.txt
     *
     * Respuesta: descarga del archivo .txt con headers apropiados.
     */
    public function descargarReporte(string $nombre)
    {
        // Sanitizar el nombre para evitar path traversal
        // Solo permitimos caracteres seguros: letras, números, guiones, puntos
        $nombreLimpio = basename($nombre);

        if (!preg_match('/^[a-zA-Z0-9_\-\.]+\.txt$/', $nombreLimpio)) {
            return response()->json([
                'message' => 'Nombre de archivo inválido.',
            ], 422);
        }

        $pathCompleto = "reportes/{$nombreLimpio}";

        if (!Storage::exists($pathCompleto)) {
            return response()->json([
                'message' => 'El archivo de reporte no fue encontrado. Puede que haya expirado.',
            ], 404);
        }

        return Storage::download($pathCompleto, $nombreLimpio, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}