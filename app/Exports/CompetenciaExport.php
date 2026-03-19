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
 * CompetenciaExport
 *
 * Exporta competencias a Excel.
 *
 * Uso sin filtro (todas):
 *   Excel::download(new CompetenciaExport(), 'competencias.xlsx');
 *
 * Uso con filtro por tipo de formación:
 *   Excel::download(new CompetenciaExport($idTipoFormacion), 'competencias_tecnologo.xlsx');
 *
 * Columnas exportadas (listas para reimportar):
 *   nombreCompetencia | codigo | tipo | nombreTipoFormacion
 */
class CompetenciaExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithTitle,
    ShouldAutoSize
{
    /**
     * @param  int|null $idTipoFormacionFiltro
     *   null  → exporta todas las competencias
     *   int   → exporta solo las de ese tipo de formación
     */
    public function __construct(
        private ?int $idTipoFormacionFiltro = null
    ) {}

    // =========================================================================
    //  DATOS
    // =========================================================================

    public function collection()
    {
        $consulta = CompetenciaModel::with('tipoFormacion')
            ->orderBy('nombreCompetencia');

        if ($this->idTipoFormacionFiltro !== null) {
            $consulta->where('idTipoFormacion', $this->idTipoFormacionFiltro);
        }

        return $consulta->get();
    }

    // =========================================================================
    //  ENCABEZADOS  (mismo nombre que espera el importador)
    // =========================================================================

    public function headings(): array
    {
        return [
            'nombreCompetencia',
            'codigo',
            'tipo',
            'nombreTipoFormacion',   // ← columna nueva para el import
        ];
    }

    // =========================================================================
    //  MAPEO
    // =========================================================================

    public function map($competencia): array
    {
        return [
            $competencia->nombreCompetencia,
            $competencia->codigo,
            $competencia->tipo,
            $competencia->tipoFormacion?->nombreTipoFormacion ?? '',
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
                    'startColor' => ['argb' => 'FF39A900'],
                ],
            ],
        ];
    }
}