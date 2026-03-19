<?php

namespace App\Exports;

use App\Models\ResultadoModel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ResultadoExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithTitle,
    ShouldAutoSize
{
    public function __construct(protected ?int $idTipoFormacion = null) {}

    public function collection()
    {
        $query = ResultadoModel::with('competencia');

        if ($this->idTipoFormacion !== null) {
            $query->whereHas('competencia', function ($q) {
                $q->where('idTipoFormacion', $this->idTipoFormacion);
            });
        }

        return $query->orderBy('idResultado')->get();
    }

    public function map($resultado): array
    {
        return [
            $resultado->idResultado,
            $resultado->nombre,
            $resultado->codigo,
            $resultado->competencia?->nombreCompetencia ?? 'Sin Competencia', // <-- nombre de la competencia
        ];
    }

    public function headings(): array
    {
        return [
            'ID Resultado',
            'Nombre Resultado',
            'Código',
            'Nombre Competencia',
        ];
    }

    public function title(): string
    {
        return 'Resultados';
    }

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
                    'startColor' => ['argb' => 'FF39A900'],
                ],
            ],
        ];
    }
}