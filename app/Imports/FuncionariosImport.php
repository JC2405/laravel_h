<?php

namespace App\Imports;

use App\Models\FuncionarioModel;
use App\Models\TipoContratoModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

/**
 * FuncionariosImport
 *
 * Importa instructores/funcionarios desde un archivo Excel.
 *
 * Formato esperado del Excel (primera fila = encabezados):
 * ┌──────────────────┬───────────┬──────────────────────┬──────────┬──────────────────┬─────────┐
 * │ nombre           │ documento │ correo               │ telefono │ tipo_contrato    │ estado  │
 * ├──────────────────┼───────────┼──────────────────────┼──────────┼──────────────────┼─────────┤
 * │ Juan Pérez       │ 12345678  │ juan@correo.com      │ 31234567 │ Contrato Obra    │ Activo  │
 * └──────────────────┴───────────┴──────────────────────┴──────────┴──────────────────┴─────────┘
 *
 * NOTA: WithHeadingRow convierte los encabezados a snake_case automáticamente.
 * Así "Nombre Completo" se convierte en "nombre_completo".
 * Por eso los encabezados del Excel deben coincidir con los nombres en headings().
 *
 * Uso desde el controller:
 *   $import = new FuncionariosImport();
 *   Excel::import($import, $request->file('archivo'));
 *   $errores = $import->failures();
 */
class FuncionariosImport implements
    ToCollection,       // recibe todas las filas como una colección de una vez
    WithHeadingRow,     // usa la primera fila como nombres de columna
    WithValidation,     // valida cada fila antes de procesarla
    SkipsOnError,       // si una fila lanza una excepción, la salta sin detener todo
    SkipsOnFailure      // si una fila falla validación, la salta sin detener todo
{
    // Estos traits implementan los métodos requeridos por SkipsOnError y SkipsOnFailure
    use SkipsErrors, SkipsFailures;

    // Contadores para reportar resultados al usuario
    private int $totalFilasImportadas  = 0;
    private int $totalFilasSaltadas    = 0;

    // =========================================================================
    //  PROCESAMIENTO DE FILAS
    // =========================================================================

    /**
     * Recibe todas las filas válidas como una colección y las procesa.
     *
     * Se usa ToCollection en lugar de ToModel para poder manejar
     * la lógica de "crear o actualizar" (upsert) por documento.
     *
     * @param  Collection $filasDeLaHoja  Todas las filas del Excel como objetos
     */
    public function collection(Collection $filasDeLaHoja): void
    {
        foreach ($filasDeLaHoja as $filaActual) {

            // Buscar el tipo de contrato por nombre para obtener su ID
            // Si no existe ese tipo de contrato, se asigna el primero disponible
            $tipoContratoEncontrado = TipoContratoModel::where(
                'nombreTipoContrato',
                $filaActual['tipo_contrato'] ?? ''
            )->first();

            $idTipoContratoAUsar = $tipoContratoEncontrado?->idTipoContrato
                ?? TipoContratoModel::first()?->idTipoContrato
                ?? 1;

            // La contraseña por defecto es el número de documento del funcionario
            // (el modelo la hasheará automáticamente con el cast 'hashed')
            $documentoDelFuncionario = trim($filaActual['documento'] ?? '');
            $contrasenaInicial       = $documentoDelFuncionario;

            // updateOrCreate: si ya existe un funcionario con ese documento,
            // actualiza sus datos. Si no existe, lo crea como nuevo.
            $funcionarioGuardado = FuncionarioModel::updateOrCreate(
                // Condición de búsqueda: buscar por documento único
                ['documento' => $documentoDelFuncionario],

                // Datos a crear o actualizar
                [
                    'nombre'         => trim($filaActual['nombre']   ?? ''),
                    'correo'         => trim($filaActual['correo']   ?? ''),
                    'telefono'       => trim($filaActual['telefono'] ?? ''),
                    'estado'         => trim($filaActual['estado']   ?? 'Activo'),
                    'idTipoContrato' => $idTipoContratoAUsar,
                    'password'       => $contrasenaInicial,
                ]
            );

            // Si el funcionario es nuevo (recién creado), asignarle el rol
            // de instructor (idRol = 2) por defecto
            if ($funcionarioGuardado->wasRecentlyCreated) {
                $funcionarioGuardado->roles()->attach(2);
            }

            $this->totalFilasImportadas++;
        }
    }

    // =========================================================================
    //  REGLAS DE VALIDACIÓN POR FILA
    // =========================================================================

    /**
     * Valida cada fila antes de procesarla.
     *
     * Si una fila no cumple estas reglas, se salta automáticamente
     * (gracias a SkipsOnFailure) y se registra en failures().
     */
    public function rules(): array
    {
        return [
            // El nombre es obligatorio y no puede ser vacío
            'nombre'   => 'required|string|max:140',

            // El documento es obligatorio (el sistema crea o actualiza por este campo)
            'documento' => 'required|string|max:40',

            // El correo debe tener formato válido
            'correo'    => 'required|email|max:160',

            // El teléfono es opcional pero si viene debe ser string
            'telefono'  => 'nullable|string|max:40',

            // Estado solo puede ser "Activo" o "Inactivo"
            'estado'    => 'nullable|in:Activo,Inactivo',
        ];
    }

    /**
     * Mensajes de error personalizados en español.
     */
    public function customValidationMessages(): array
    {
        return [
            'nombre.required'    => 'La columna "nombre" es obligatoria.',
            'documento.required' => 'La columna "documento" es obligatoria.',
            'correo.required'    => 'La columna "correo" es obligatoria.',
            'correo.email'       => 'El correo ":input" no tiene un formato válido.',
            'estado.in'          => 'El estado debe ser "Activo" o "Inactivo".',
        ];
    }

    // =========================================================================
    //  REPORTES DE RESULTADO
    // =========================================================================

    /** Cuántas filas se importaron correctamente */
    public function getTotalImportadas(): int
    {
        return $this->totalFilasImportadas;
    }

    /** Cuántas filas se saltaron por errores de validación */
    public function getTotalSaltadas(): int
    {
        return count($this->failures()) + count($this->errors());
    }
}
