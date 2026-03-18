<?php

namespace App\Exports;

use App\Models\CompetenciaModel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * CompetenciasExport
 *
 * Exporta todas las competencias a Excel.
 *
 * Uso:
 *   return Excel::download(new CompetenciasExport, 'competencias.xlsx');
 */
class CompetenciaExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithTitle,
    ShouldAutoSize
{
    // =========================================================================
    //  DATOS
    // =========================================================================

    public function collection()
    {
        return CompetenciaModel::orderBy('nombreCompetencia')->get();
    }

    // =========================================================================
    //  ENCABEZADOS
    // =========================================================================

    public function headings(): array
    {
        return [
            'ID',
            'nombreCompetencia',
            'codigo',
            'tipo',
        ];
    }

    // =========================================================================
    //  MAPEO
    // =========================================================================

    public function map($competencia): array
    {
        return [
            $competencia->idCompetencia,
            $competencia->nombreCompetencia,
            $competencia->codigo,
            $competencia->tipo,
        ];
    }

    // =========================================================================
    //  NOMBRE DE PESTAÑA
    // =========================================================================

    public function title(): string
    {
        return 'Competencias';
    }

    // =========================================================================
    //  ESTILOS
    // =========================================================================

    public function styles(Worksheet $hoja): array
    {
        return [
            1 => [
                'font' => [
                    'bold'  => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType'   => 'solid',
                    'startColor' => ['argb' => 'FF39A900'], // verde SENA
                ],
            ],
        ];
    }
}