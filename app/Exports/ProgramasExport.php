<?php

namespace App\Exports;

use App\Models\ProgramaModel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * ProgramasExport
 *
 * Exporta todos los programas de formación a Excel.
 *
 * Incluye el nombre, código, versión, tipo de formación,
 * duración en meses y estado de cada programa.
 *
 * Uso desde el controller:
 *   return Excel::download(new ProgramasExport, 'programas.xlsx');
 */
class ProgramasExport implements
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
        return ProgramaModel::with([
            'tipoFormacion', // para mostrar nombre y duración del tipo
        ])
        ->orderBy('nombre')
        ->get();
    }

    // =========================================================================
    //  ENCABEZADOS
    // =========================================================================

    public function headings(): array
    {
        return [
            'ID Programa',
            'Nombre del Programa',
            'Código',
            'Versión',
            'Tipo de Formación',
            'Duración (meses)',
            'Estado',
        ];
    }

    // =========================================================================
    //  MAPEO
    // =========================================================================

    /**
     * @param  ProgramaModel $programa
     * @return array
     */
    public function map($programa): array
    {
        $nombreTipoFormacion = $programa->tipoFormacion?->nombreTipoFormacion ?? 'Sin tipo';
        $duracionMeses       = $programa->tipoFormacion?->duracionMeses       ?? '—';

        return [
            $programa->idPrograma,
            $programa->nombre,
            $programa->codigo,
            $programa->version,
            $nombreTipoFormacion,
            $duracionMeses,
            $programa->estado,
        ];
    }

    // =========================================================================
    //  NOMBRE DE PESTAÑA
    // =========================================================================

    public function title(): string
    {
        return 'Programas';
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
