<?php

namespace App\Imports;

use App\Models\FuncionarioModel;
use App\Models\TipoContratoModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Validators\Failure;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use Throwable;

class FuncionariosImport extends DefaultValueBinder implements
    ToCollection,
    WithHeadingRow,
    WithValidation,
    WithCustomValueBinder,
    SkipsEmptyRows,
    SkipsOnError,
    SkipsOnFailure
{
    use SkipsErrors, SkipsFailures;

    private int $totalFilasImportadas = 0;
    private int $totalFilasSaltadas   = 0;

    // =========================================================================
    //  VALUE BINDER
    // =========================================================================

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_numeric($value) && $value !== '' && $value !== null) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    // =========================================================================
    //  PROCESAMIENTO DE FILAS
    // =========================================================================

    public function collection(Collection $filasDeLaHoja): void
    {
        foreach ($filasDeLaHoja as $filaActual) {

            // Leer y limpiar campos obligatorios
            $nombre    = trim((string) ($filaActual['nombre']    ?? ''));
            $apellido  = trim((string) ($filaActual['apellido']  ?? ''));
            $documento = trim((string) ($filaActual['documento'] ?? ''));
            $correo    = trim((string) ($filaActual['correo']    ?? ''));

            // ✅ Saltar filas donde algún campo obligatorio esté vacío
            if ($nombre === '' || $apellido === '' || $documento === '' || $correo === '') {
                $this->totalFilasSaltadas++;
                continue;
            }

            // Buscar tipo de contrato por nombre; si no existe, usar el primero disponible
            $tipoContratoEncontrado = TipoContratoModel::where(
                'nombreTipoContrato',
                trim($filaActual['tipo_contrato'] ?? '')
            )->first();

            $idTipoContratoAUsar = $tipoContratoEncontrado?->idTipoContrato
                ?? TipoContratoModel::first()?->idTipoContrato
                ?? 1;

            $contrasenaInicial = $documento;

            $funcionarioGuardado = FuncionarioModel::updateOrCreate(
                ['documento' => $documento],
                [
                    'nombre'         => $nombre,
                    'apellido'       => $apellido,
                    'correo'         => $correo,
                    'telefono'       => trim((string) ($filaActual['telefono'] ?? '')),
                    'estado'         => trim((string) ($filaActual['estado']   ?? 'Activo')),
                    'idTipoContrato' => $idTipoContratoAUsar,
                    'password'       => $contrasenaInicial,
                ]
            );

            if ($funcionarioGuardado->wasRecentlyCreated) {
                $funcionarioGuardado->roles()->attach(2);
            }

            $this->totalFilasImportadas++;
        }
    }

    // =========================================================================
    //  REGLAS DE VALIDACIÓN
    // =========================================================================

    /**
     * ✅ Todos nullable para que las filas vacías no generen errores de validación.
     *    La obligatoriedad se maneja manualmente en collection() con el continue.
     */
    public function rules(): array
    {
        return [
            'nombre'    => 'nullable|string|max:140',
            'apellido'  => 'nullable|string|max:140',
            'documento' => 'nullable|string|max:40',
            'correo'    => 'nullable|email|max:160',
            'telefono'  => 'nullable|string|max:40',
            'estado'    => 'nullable|in:Activo,Inactivo',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'correo.email' => 'El correo ":input" no tiene un formato válido.',
            'estado.in'    => 'El estado debe ser "Activo" o "Inactivo".',
        ];
    }

    // =========================================================================
    //  REPORTES DE RESULTADO
    // =========================================================================

    public function getTotalImportadas(): int
    {
        return $this->totalFilasImportadas;
    }

    public function getTotalSaltadas(): int
    {
        return $this->totalFilasSaltadas + count($this->failures()) + count($this->errors());
    }
}