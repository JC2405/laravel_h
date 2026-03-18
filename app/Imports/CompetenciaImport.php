<?php

namespace App\Imports;

use App\Models\CompetenciaModel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

/**
 * CompetenciasImport
 *
 * Importa competencias desde un archivo Excel.
 *
 * Formato esperado del Excel (primera fila = encabezados):
 * ┌──────────────────────┬──────────┬──────────────┐
 * │ nombreCompetencia    │ codigo   │ tipo         │
 * ├──────────────────────┼──────────┼──────────────┤
 * │ Análisis de Sistemas │ 228186   │ Titulada     │
 * └──────────────────────┴──────────┴──────────────┘
 *
 * NOTA: WithHeadingRow convierte los encabezados a snake_case/lowercase automáticamente.
 * Por eso en el array accedemos con 'nombrecompetencia' (todo minúsculas).
 */
class CompetenciaImport implements
    ToCollection,
    WithHeadingRow,
    WithValidation,
    SkipsOnError,
    SkipsOnFailure
{
    use SkipsErrors, SkipsFailures;

    private int $totalFilasImportadas = 0;

    // =========================================================================
    //  PROCESAMIENTO
    // =========================================================================

    public function collection(Collection $filasDeLaHoja): void
    {
        foreach ($filasDeLaHoja as $fila) {

            $codigo = trim($fila['codigo'] ?? '');

            // Saltar filas con código vacío
            if (empty($codigo)) {
                continue;
            }

            CompetenciaModel::updateOrCreate(
                // Buscar por código único
                ['codigo' => $codigo],

                // Datos a crear o actualizar
                // WithHeadingRow convierte "nombreCompetencia" → "nombrecompetencia"
                [
                    'nombreCompetencia' => trim($fila['nombrecompetencia'] ?? ''),
                    'tipo'              => trim($fila['tipo'] ?? ''),
                ]
            );

            $this->totalFilasImportadas++;
        }
    }

    // =========================================================================
    //  VALIDACIÓN POR FILA
    // =========================================================================

    public function rules(): array
    {
        return [
            'nombrecompetencia' => 'required|string|max:200',
            'codigo'            => 'required|string|max:40',
            'tipo'              => 'required|string|max:50',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nombrecompetencia.required' => 'La columna "nombreCompetencia" es obligatoria.',
            'codigo.required'            => 'La columna "codigo" es obligatoria.',
            'tipo.required'              => 'La columna "tipo" es obligatoria.',
        ];
    }

    // =========================================================================
    //  REPORTES
    // =========================================================================

    public function getTotalImportadas(): int
    {
        return $this->totalFilasImportadas;
    }

    public function getTotalSaltadas(): int
    {
        return count($this->failures()) + count($this->errors());
    }
}