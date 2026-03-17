<?php

namespace App\Http\Controllers;

use App\Exports\AprendicesExport;
use App\Exports\FichasExport;
use App\Exports\FuncionariosExport;
use App\Exports\ProgramasExport;
use App\Imports\AprendicesImport;
use App\Imports\FuncionariosImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * ExcelController
 *
 * Gestiona todas las operaciones de exportación e importación de Excel.
 *
 * RUTAS a agregar en api.php:
 *
 *   // ── Exports (descargar archivo) ────────────────────────────────────────
 *   Route::get('exportar/funcionarios',          [ExcelController::class, 'exportarFuncionarios']);
 *   Route::get('exportar/fichas',                [ExcelController::class, 'exportarFichas']);
 *   Route::get('exportar/aprendices',            [ExcelController::class, 'exportarAprendices']);
 *   Route::get('exportar/aprendices/{idFicha}',  [ExcelController::class, 'exportarAprendicesDeFicha']);
 *   Route::get('exportar/programas',             [ExcelController::class, 'exportarProgramas']);
 *
 *   // ── Imports (subir archivo) ────────────────────────────────────────────
 *   Route::post('importar/funcionarios', [ExcelController::class, 'importarFuncionarios']);
 *   Route::post('importar/aprendices',   [ExcelController::class, 'importarAprendices']);
 */
class ExcelController extends Controller
{
    // =========================================================================
    //  EXPORTS — Descargar Excel
    // =========================================================================

    /**
     * GET /api/exportar/funcionarios
     *
     * Descarga un Excel con todos los instructores del sistema.
     * El nombre del archivo incluye la fecha para facilitar el archivo histórico.
     */
    public function exportarFuncionarios()
    {
        // Nombre del archivo con la fecha actual: "funcionarios_2024-03-15.xlsx"
        $nombreArchivo = 'funcionarios_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new FuncionariosExport, $nombreArchivo);
    }

    /**
     * GET /api/exportar/fichas
     *
     * Descarga un Excel con todas las fichas de formación.
     */
    public function exportarFichas()
    {
        $nombreArchivo = 'fichas_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new FichasExport, $nombreArchivo);
    }

    /**
     * GET /api/exportar/aprendices
     *
     * Descarga un Excel con TODOS los aprendices del sistema.
     */
    public function exportarAprendices()
    {
        $nombreArchivo = 'aprendices_' . now()->format('Y-m-d') . '.xlsx';

        // null = sin filtro de ficha → exportar todos
        return Excel::download(new AprendicesExport(null), $nombreArchivo);
    }

    /**
     * GET /api/exportar/aprendices/{idFicha}
     *
     * Descarga un Excel con los aprendices de UNA ficha específica.
     * Útil para entregar la lista de una clase a un instructor.
     */
    public function exportarAprendicesDeFicha(int $idFicha)
    {
        $nombreArchivo = "aprendices_ficha_{$idFicha}_" . now()->format('Y-m-d') . '.xlsx';

        // Pasar el idFicha para filtrar solo esa ficha
        return Excel::download(new AprendicesExport($idFicha), $nombreArchivo);
    }

    /**
     * GET /api/exportar/programas
     *
     * Descarga un Excel con todos los programas de formación.
     */
    public function exportarProgramas()
    {
        $nombreArchivo = 'programas_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new ProgramasExport, $nombreArchivo);
    }

    // =========================================================================
    //  IMPORTS — Subir Excel
    // =========================================================================

    /**
     * POST /api/importar/funcionarios
     *
     * Importa instructores desde un archivo Excel.
     *
     * Body (multipart/form-data):
     *   archivo: [archivo.xlsx]
     *
     * El Excel debe tener estas columnas en la primera fila:
     *   nombre | documento | correo | telefono | tipo_contrato | estado
     *
     * - Si el documento ya existe en la BD → actualiza los datos.
     * - Si no existe → crea un nuevo funcionario.
     * - La contraseña inicial es el número de documento.
     * - Las filas con errores de validación se saltan (no detienen la importación).
     */
    public function importarFuncionarios(Request $request)
    {
        // Validar que se subió un archivo con extensión válida
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:5120', // máximo 5MB
        ], [
            'archivo.required' => 'Debes subir un archivo.',
            'archivo.mimes'    => 'El archivo debe ser Excel (.xlsx, .xls) o CSV.',
            'archivo.max'      => 'El archivo no puede superar los 5MB.',
        ]);

        // Crear la instancia del importador
        $importador = new FuncionariosImport();

        // Ejecutar la importación con el archivo subido
        Excel::import($importador, $request->file('archivo'));

        // Recoger los errores de validación de filas individuales
        $erroresDeFila = $importador->failures();

        // Construir la respuesta con el resumen de la operación
        $respuesta = [
            'message'     => 'Importación completada.',
            'importados'  => $importador->getTotalImportadas(),
            'con_errores' => count($erroresDeFila),
        ];

        // Si hubo filas con errores, incluirlas en la respuesta para que
        // el usuario sepa exactamente qué filas fallaron y por qué
        if (count($erroresDeFila) > 0) {
            $respuesta['errores'] = collect($erroresDeFila)->map(function ($errorDeFila) {
                return [
                    // Número de fila en el Excel (incluyendo la fila de encabezados)
                    'fila'    => $errorDeFila->row(),
                    // Columna que falló
                    'columna' => $errorDeFila->attribute(),
                    // Valor que se intentó guardar
                    'valor'   => $errorDeFila->values()[$errorDeFila->attribute()] ?? null,
                    // Mensaje de error en español
                    'errores' => $errorDeFila->errors(),
                ];
            })->values()->all();
        }

        return response()->json($respuesta, 200);
    }

    /**
     * POST /api/importar/aprendices
     *
     * Importa aprendices desde un archivo Excel.
     *
     * Body (multipart/form-data):
     *   archivo:   [archivo.xlsx]
     *   id_ficha:  (opcional) Si se envía, todos los aprendices se asignan a esa ficha.
     *              Si no se envía, se lee la columna "codigo_ficha" de cada fila.
     *
     * El Excel debe tener estas columnas en la primera fila:
     *   nombre | documento | correo | telefono | estado | codigo_ficha
     *
     * La columna "codigo_ficha" solo es necesaria si no se pasa "id_ficha" en el body.
     */
    public function importarAprendices(Request $request)
    {
        $request->validate([
            'archivo'  => 'required|file|mimes:xlsx,xls,csv|max:5120',
            // id_ficha es opcional: si se pasa, debe ser un ID válido de la tabla ficha
            'id_ficha' => 'nullable|integer|exists:ficha,idFicha',
        ], [
            'archivo.required'  => 'Debes subir un archivo.',
            'archivo.mimes'     => 'El archivo debe ser Excel (.xlsx, .xls) o CSV.',
            'id_ficha.exists'   => 'La ficha indicada no existe en el sistema.',
        ]);

        // Leer el idFicha del body si se envió, o null si no
        $idFichaDelBody = $request->input('id_ficha') ? (int) $request->input('id_ficha') : null;

        $importador = new AprendicesImport(idFichaFija: $idFichaDelBody);

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
}