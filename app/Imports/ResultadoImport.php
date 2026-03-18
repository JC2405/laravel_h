<?php

namespace App\Imports;

use App\Models\CompetenciaModel;
use App\Models\ResultadoModel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class ResultadoImport implements
    ToCollection,
    WithHeadingRow,
    WithValidation,
    SkipsOnError,
    SkipsOnFailure
{
    use SkipsErrors, SkipsFailures;

    private int $totalFilasImportadas = 0;

    public function collection(Collection $filasDeLaHoja): void
    {
        foreach ($filasDeLaHoja as $fila) {
            $codigo           = trim($fila['codigo']            ?? '');
            $codigoCompetencia = trim($fila['codigo_competencia'] ?? '');

            if (empty($codigo) || empty($codigoCompetencia)) {
                continue;
            }

            // Buscar la competencia por su código para obtener el idCompetencia
            $competencia = CompetenciaModel::where('codigo', $codigoCompetencia)->first();

            if (!$competencia) {
                continue; // Si no existe la competencia, saltamos la fila
            }

            ResultadoModel::updateOrCreate(
                ['codigo' => $codigo],
                [
                    'nombre'        => trim($fila['nombre'] ?? ''),
                    'idCompetencia' => $competencia->idCompetencia,
                ]
            );

            $this->totalFilasImportadas++;
        }
    }

    public function rules(): array
    {
        return [
            'nombre'            => 'required|string|max:255',
            'codigo'            => 'required|string|max:40',
            'codigo_competencia' => 'required|string|max:40',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nombre.required'             => 'La columna "nombre" es obligatoria.',
            'codigo.required'             => 'La columna "codigo" es obligatoria.',
            'codigo_competencia.required' => 'La columna "codigo_competencia" es obligatoria.',
        ];
    }

    public function getTotalImportadas(): int
    {
        return $this->totalFilasImportadas;
    }

    public function getTotalSaltadas(): int
    {
        return count($this->failures()) + count($this->errors());
    }
}