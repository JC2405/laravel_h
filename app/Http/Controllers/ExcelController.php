<?php

namespace App\Http\Controllers;

use App\Exports\AprendicesExport;
use App\Exports\CompetenciaExport;
use App\Exports\FichasExport;
use App\Exports\FuncionariosExport;
use App\Exports\ProgramasExport;
use App\Exports\ResultadoExport;
use App\Imports\AprendicesImport;
use App\Imports\CompetenciaImport;
use App\Imports\FuncionariosImport;
use App\Imports\ResultadoImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * ExcelController
 *
 * Gestiona todas las operaciones de exportación e importación de Excel.
 *
 * RUTAS en api.php:
 *
 *   // ── Exports ────────────────────────────────────────────────────────────
 *   Route::get('exportar/funcionarios',          [ExcelController::class, 'exportarFuncionarios']);
 *   Route::get('exportar/fichas',                [ExcelController::class, 'exportarFichas']);
 *   Route::get('exportar/aprendices',            [ExcelController::class, 'exportarAprendices']);
 *   Route::get('exportar/aprendices/{idFicha}',  [ExcelController::class, 'exportarAprendicesDeFicha']);
 *   Route::get('exportar/programas',             [ExcelController::class, 'exportarProgramas']);
 *   Route::get('exportar/competencias',          [ExcelController::class, 'exportarCompetencias']);
 *
 *   // ── Imports ────────────────────────────────────────────────────────────
 *   Route::post('importar/funcionarios',  [ExcelController::class, 'importarFuncionarios']);
 *   Route::post('importar/aprendices',    [ExcelController::class, 'importarAprendices']);
 *   Route::post('importar/competencias',  [ExcelController::class, 'importarCompetencias']);
 */
class ExcelController extends Controller
{
    // =========================================================================
    //  EXPORTS — Descargar Excel
    // =========================================================================

    public function exportarFuncionarios()
    {
        $nombreArchivo = 'funcionarios_' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new FuncionariosExport, $nombreArchivo);
    }

    public function exportarFichas()
    {
        $nombreArchivo = 'fichas_' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new FichasExport, $nombreArchivo);
    }

    public function exportarAprendices()
    {
        $nombreArchivo = 'aprendices_' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new AprendicesExport(null), $nombreArchivo);
    }

    public function exportarAprendicesDeFicha(int $idFicha)
    {
        $nombreArchivo = "aprendices_ficha_{$idFicha}_" . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new AprendicesExport($idFicha), $nombreArchivo);
    }

    public function exportarProgramas()
    {
        $nombreArchivo = 'programas_' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new ProgramasExport, $nombreArchivo);
    }

    /**
     * GET /api/exportar/competencias
     *
     * Descarga un Excel con todas las competencias del sistema.
     * Columnas: ID | nombreCompetencia | codigo | tipo
     */
    public function exportarCompetencias(Request $request)
    {
    $idTipoFormacion = $request->query('id_tipo_formacion')
        ? (int) $request->query('id_tipo_formacion')
        : null;

    // Validar que el id existe si fue enviado
    if ($idTipoFormacion !== null) {
        $request->merge(['id_tipo_formacion' => $idTipoFormacion]);
        $request->validate([
            'id_tipo_formacion' => 'integer|exists:tipoFormacion,idTipoFormacion',
        ], [
            'id_tipo_formacion.exists' => 'El tipo de formación indicado no existe.',
        ]);
    }

    $sufijo        = $idTipoFormacion ? "tipo_{$idTipoFormacion}_" : '';
    $nombreArchivo = "competencias_{$sufijo}" . now()->format('Y-m-d') . '.xlsx';

    return Excel::download(new CompetenciaExport($idTipoFormacion), $nombreArchivo);
    }


    // =========================================================================
    //  IMPORTS — Subir Excel
    // =========================================================================

    public function importarFuncionarios(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'archivo.required' => 'Debes subir un archivo.',
            'archivo.mimes'    => 'El archivo debe ser Excel (.xlsx, .xls) o CSV.',
            'archivo.max'      => 'El archivo no puede superar los 5MB.',
        ]);

        $importador = new FuncionariosImport();
        Excel::import($importador, $request->file('archivo'));

        $erroresDeFila = $importador->failures();

        $respuesta = [
            'message'     => 'Importación completada.',
            'importados'  => $importador->getTotalImportadas(),
            'con_errores' => count($erroresDeFila),
        ];

        if (count($erroresDeFila) > 0) {
            $respuesta['errores'] = collect($erroresDeFila)->map(function ($errorDeFila) {
                return [
                    'fila'    => $errorDeFila->row(),
                    'columna' => $errorDeFila->attribute(),
                    'valor'   => $errorDeFila->values()[$errorDeFila->attribute()] ?? null,
                    'errores' => $errorDeFila->errors(),
                ];
            })->values()->all();
        }

        return response()->json($respuesta, 200);
    }

    public function importarAprendices(Request $request)
    {
        $request->validate([
            'archivo'  => 'required|file|mimes:xlsx,xls,csv|max:5120',
            'id_ficha' => 'required|integer|exists:ficha,idFicha',
        ], [
            'archivo.required'  => 'Debes subir un archivo.',
            'archivo.mimes'     => 'El archivo debe ser Excel (.xlsx, .xls) o CSV.',
            'id_ficha.required' => 'Debes especificar la ficha para importar.',
            'id_ficha.exists'   => 'La ficha indicada no existe en el sistema.',
        ]);

        $idFichaDelBody = (int) $request->input('id_ficha');
        $importador     = new AprendicesImport(idFichaFija: $idFichaDelBody);

        Excel::import($importador, $request->file('archivo'));

        $erroresDeFila = $importador->failures();

        $respuesta = [
            'message'     => 'Importación completada.',
            'importados'  => $importador->getTotalImportados(),
            'con_errores' => count($erroresDeFila),
        ];

        if (count($erroresDeFila) > 0) {
            $respuesta['errores'] = collect($erroresDeFila)->map(function ($errorDeFila) {
                return [
                    'fila'    => $errorDeFila->row(),
                    'columna' => $errorDeFila->attribute(),
                    'valor'   => $errorDeFila->values()[$errorDeFila->attribute()] ?? null,
                    'errores' => $errorDeFila->errors(),
                ];
            })->values()->all();
        }

        return response()->json($respuesta, 200);
    }

    /**
     * POST /api/importar/competencias
     *
     * Importa competencias desde un archivo Excel.
     *
     * Body (multipart/form-data):
     *   archivo: [archivo.xlsx]
     *
     * El Excel debe tener estas columnas en la primera fila:
     *   nombreCompetencia | codigo | tipo
     *
     * - Si el codigo ya existe en la BD → actualiza los datos.
     * - Si no existe → crea una nueva competencia.
     * - Las filas con errores de validación se saltan.
     */
    public function importarCompetencias(Request $request)
{
    $request->validate([
        'archivo'           => 'required|file|mimes:xlsx,xls,csv|max:5120',
        'id_tipo_formacion' => 'nullable|integer|exists:tipoFormacion,idTipoFormacion',
    ], [
        'archivo.required'          => 'Debes subir un archivo.',
        'archivo.mimes'             => 'El archivo debe ser Excel (.xlsx, .xls) o CSV.',
        'archivo.max'               => 'El archivo no puede superar los 5MB.',
        'id_tipo_formacion.exists'  => 'El tipo de formación indicado no existe.',
    ]);

    $idTipoFormacion = $request->input('id_tipo_formacion')
        ? (int) $request->input('id_tipo_formacion')
        : null;

    $importador = new CompetenciaImport(idTipoFormacionFijo: $idTipoFormacion);
    Excel::import($importador, $request->file('archivo'));

    $erroresDeFila = $importador->failures();

    $respuesta = [
        'message'     => 'Importación completada.',
        'importados'  => $importador->getTotalImportadas(),
        'con_errores' => count($erroresDeFila),
    ];

    if (count($erroresDeFila) > 0) {
        $respuesta['errores'] = collect($erroresDeFila)->map(function ($errorDeFila) {
            return [
                'fila'    => $errorDeFila->row(),
                'columna' => $errorDeFila->attribute(),
                'valor'   => $errorDeFila->values()[$errorDeFila->attribute()] ?? null,
                'errores' => $errorDeFila->errors(),
            ];
        })->values()->all();
    }

    return response()->json($respuesta, 200);
    }


    public function exportarResultados(Request $request)
    {
        $idTipoFormacion = $request->query('id_tipo_formacion')
            ? (int) $request->query('id_tipo_formacion')
            : null;

        if ($idTipoFormacion !== null) {
            $request->merge(['id_tipo_formacion' => $idTipoFormacion]);
            $request->validate([
                'id_tipo_formacion' => 'integer|exists:tipoFormacion,idTipoFormacion',
            ], [
                'id_tipo_formacion.exists' => 'El tipo de formación indicado no existe.',
            ]);
        }

        $sufijo = $idTipoFormacion ? "tipo_{$idTipoFormacion}_" : '';
        $nombreArchivo = "resultados_{$sufijo}" . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new ResultadoExport($idTipoFormacion), $nombreArchivo);
    }

    public function importarResultados(Request $request)
    {
        // Validación del archivo
        $request->validate([
            'archivo'           => 'required|file|mimes:xlsx,xls,csv|max:5120',
            'id_tipo_formacion' => 'nullable|integer|exists:tipoFormacion,idTipoFormacion',
        ], [
            'archivo.required'          => 'Debes subir un archivo.',
            'archivo.mimes'             => 'El archivo debe ser Excel (.xlsx, .xls) o CSV.',
            'archivo.max'               => 'El archivo no puede superar los 5MB.',
            'id_tipo_formacion.exists'  => 'El tipo de formación indicado no existe.',
        ]);

        $idTipoFormacion = $request->input('id_tipo_formacion')
            ? (int) $request->input('id_tipo_formacion')
            : null;

        // Crear importador
        $importador = new ResultadoImport(idTipoFormacionFijo: $idTipoFormacion);

    // Importar archivo
    Excel::import($importador, $request->file('archivo'));

    // Obtener errores y filas importadas
    $erroresDeFila = $importador->failures();

    $respuesta = [
        'message'     => 'Importación completada.',
        'importados'  => $importador->getTotalImportadas(),
        'con_errores' => count($erroresDeFila),
    ];

    if (count($erroresDeFila) > 0) {
        $respuesta['errores'] = collect($erroresDeFila)->map(function ($errorDeFila) {
            return [
                'fila'    => $errorDeFila->row(),
                'columna' => $errorDeFila->attribute(),
                'valor'   => $errorDeFila->values()[$errorDeFila->attribute()] ?? null,
                'errores' => $errorDeFila->errors(),
            ];
        })->values()->all();
    }

    return response()->json($respuesta, 200);
    }
}