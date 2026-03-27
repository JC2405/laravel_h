<?php

namespace App\Imports;

use App\Models\AprendizModel;
use App\Models\FichaModel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

/**
 * AprendicesImport
 *
 * Importa aprendices desde un archivo Excel y los asigna a una ficha.
 *
 * Formato esperado del Excel (primera fila = encabezados):
 * ┌──────────────────┬───────────┬──────────────────────┬──────────┬─────────┐
 * │ nombre           │ documento │ correo               │ telefono │ estado  │
 * ├──────────────────┼───────────┼──────────────────────┼──────────┼─────────┤
 * │ María López      │ 98765432  │ maria@correo.com     │ 31198765 │ Activo  │
 * └──────────────────┴───────────┴──────────────────────┴──────────┴─────────┘
 *
 * El idFicha se pasa al constructor para asignar todos los aprendices
 * del archivo a esa ficha, ya que se sube desde el panel de la ficha.
 *
 * Uso desde el controller:
 *   $import = new AprendicesImport(idFichaFija: 5);
 *   Excel::import($import, $request->file('archivo'));
 */
class AprendicesImport implements
    ToCollection,
    WithHeadingRow,
    WithValidation,
    SkipsOnError,
    SkipsOnFailure
{
    use SkipsErrors, SkipsFailures;

    private int $totalImportados = 0;

    /**
     * $idFichaFija se usa para que todos los aprendices del archivo
     * se asignen de forma automática a la ficha.
     */
    public function __construct(
        private int $idFichaFija
    ) {}

    // =========================================================================
    //  PROCESAMIENTO
    // =========================================================================

    public function collection(Collection $filasDeLaHoja): void
    {
        foreach ($filasDeLaHoja as $filaActual) {

            // ── Crear o actualizar el aprendiz ────────────────────────────────

            $documentoDelAprendiz = trim($filaActual['documento'] ?? '');

            // La contraseña inicial es el número de documento del aprendiz
            $contrasenaInicial = $documentoDelAprendiz;

            AprendizModel::updateOrCreate(
                // Buscar por documento (identificador único del aprendiz)
                ['documento' => $documentoDelAprendiz],

                // Datos a insertar o actualizar
                [
                    'nombre'   => trim($filaActual['nombre']   ?? ''),
                    'apellido' => trim($filaActual['apellido'] ?? ''),
                    'correo'   => trim($filaActual['correo']   ?? ''),
                    'telefono' => trim($filaActual['telefono'] ?? ''),
                    'estado'   => trim($filaActual['estado']   ?? 'Activo'),
                    'idFicha'  => $this->idFichaFija,
                    'password' => $contrasenaInicial,
                ]
            );

            $this->totalImportados++;
        }
    }

    // =========================================================================
    //  VALIDACIÓN
    // =========================================================================

    public function rules(): array
    {
        return [
            'nombre'    => 'required|string|max:140',
            'apellido' => 'required|string|max:140',
            'documento' => 'required|string|max:40',
            'correo'    => 'required|email|max:160',
            'telefono'  => 'nullable|string|max:40',
            'estado'    => 'nullable|in:Activo,Inactivo',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nombre.required'    => 'La columna "nombre" es obligatoria.',
            'apellido.required' => 'La columna "apellido" es obligatoria',
            'documento.required' => 'La columna "documento" es obligatoria.',
            'correo.required'    => 'La columna "correo" es obligatoria.',
            'correo.email'       => 'El correo ":input" no tiene formato válido.',
            'estado.in'          => 'El estado debe ser "Activo" o "Inactivo".',
        ];
    }

    // =========================================================================
    //  REPORTE
    // =========================================================================

    public function getTotalImportados(): int
    {
        return $this->totalImportados;
    }
}
