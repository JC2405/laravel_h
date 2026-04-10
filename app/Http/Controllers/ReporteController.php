<?php

namespace App\Http\Controllers;

use App\Services\Juicios\JuiciosEvaluativosService;
use App\Services\Juicios\ReporteCompetenciasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * ReporteController
 *
 * Centraliza todos los endpoints de análisis de juicios evaluativos.
 *
 * RUTAS en api.php:
 *
 *   // Análisis rápido — solo lee el Excel, sin consultar BD
 *   // Lo usa HorarioTitulada para verificar Transversales
 *   Route::post('analizar/juicios', [ReporteController::class, 'analizarJuicios']);
 *
 *   // Análisis completo — cruza Excel con competencias y resultados de BD
 *   // Lo usa HorarioFormativa para ver qué competencias están pendientes
 *   Route::post('reportes/competencias-pendientes', [ReporteController::class, 'generarReporteCompetencias']);
 *
 *   // Descarga un reporte .txt ya generado
 *   Route::get('reportes/descargar/{nombre}', [ReporteController::class, 'descargarReporte']);
 */
class ReporteController extends Controller
{
    public function __construct(
        protected ReporteCompetenciasService $reporteService,
        protected JuiciosEvaluativosService  $juiciosService
    ) {}



    /**
     * POST /api/analizar/juicios
     *
     * Lee el Excel de juicios evaluativos y calcula porcentajes de aprobación
     * por resultado, SIN cruzar con la BD.
     * Body (multipart/form-data):
     *   archivo: [ReporteJuiciosEvaluativos.xlsx]
     */
    public function analizarJuicios(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls|max:10240',
        ], [
            'archivo.required' => 'Debes subir el reporte de juicios evaluativos.',
            'archivo.mimes'    => 'El archivo debe ser Excel (.xlsx o .xls).',
            'archivo.max'      => 'El archivo no puede superar los 10MB.',
        ]);

        try {
            $resultado = $this->juiciosService->analizar($request->file('archivo'));
            return response()->json($resultado, 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al procesar el archivo: ' . $e->getMessage(),
            ], 422);
        }
    }

    // =========================================================================
    //  ANÁLISIS COMPLETO — Excel + BD (competencias y resultados)
    //  Usado por HorarioFormativa
    // =========================================================================

    /**
     * POST /api/reportes/competencias-pendientes
     *
     * Recibe el Excel de juicios evaluativos y el ID de ficha,
     * cruza con las competencias y resultados de BD para el tipoFormacion
     * del programa de esa ficha, y retorna qué competencias están pendientes.
     *
     * Flujo:
     *   ficha → programa → tipoFormacion
     *   → competencias de BD con ese tipoFormacion (+ sus resultados)
     *   → cruce Excel vs BD → pendientes / cubiertas
     *   → genera archivo .txt en storage
     *
     * Body (multipart/form-data):
     *   archivo:   [ReporteJuiciosEvaluativos.xlsx]
     *   id_ficha:  123  (ID numérico de la ficha en la tabla ficha)
     *
     * Respuesta exitosa (200):
     * {
     *   "ok": true,
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
            $resultado = $this->reporteService->generarReporte(
                archivo: $request->file('archivo'),
                idFicha: (int) $request->input('id_ficha')
            );

            // El service retorna ['ok' => false, 'mensaje' => '...'] si hay error de negocio
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
     * Descarga un archivo .txt de reporte previamente generado por
     * generarReporteCompetencias().
     *
     * El parámetro {nombre} es solo el nombre del archivo (sin el prefijo
     * "reportes/") retornado en el campo path_txt de la respuesta anterior.
     *
     * Ejemplo:
     *   GET /api/reportes/descargar/reporte_ficha_3171062_5_20260319_143000.txt
     */
    public function descargarReporte(string $nombre)
    {
        // Sanitizar para evitar path traversal — solo caracteres seguros
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