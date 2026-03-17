<?php

namespace App\Exports;

use App\Models\FichaModel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * FichasExport
 *
 * Exporta todas las fichas (grupos de aprendices) a Excel.
 *
 * Cada fila incluye: código de ficha, programa al que pertenece,
 * tipo de formación, jornada, modalidad, fechas y estado.
 *
 * Uso desde el controller:
 *   return Excel::download(new FichasExport, 'fichas.xlsx');
 */
class FichasExport implements
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

    /**
     * Carga las fichas con sus relaciones anidadas.
     *
     * programa.tipoFormacion: necesitamos el nombre del tipo de formación
     * que está dos niveles de relación abajo.
     */
    public function collection()
    {
        return FichaModel::with([
            'programa.tipoFormacion', // ficha → programa → tipo de formación
        ])
        ->orderBy('codigoFicha')
        ->get();
    }

    // =========================================================================
    //  ENCABEZADOS
    // =========================================================================

    public function headings(): array
    {
        return [
            'ID Ficha',
            'Código Ficha',
            'Programa',
            'Código Programa',
            'Tipo de Formación',
            'Duración (meses)',
            'Jornada',
            'Modalidad',
            'Fecha Inicio',
            'Fecha Fin',
            'Estado',
        ];
    }

    // =========================================================================
    //  MAPEO (modelo → fila)
    // =========================================================================

    /**
     * @param  FichaModel $ficha
     * @return array
     */
    public function map($ficha): array
    {
        // Acceso seguro a las relaciones anidadas con el operador ?->
        $nombrePrograma       = $ficha->programa?->nombre             ?? 'Sin programa';
        $codigoPrograma       = $ficha->programa?->codigo             ?? '—';
        $nombreTipoFormacion  = $ficha->programa?->tipoFormacion?->nombreTipoFormacion ?? 'Sin tipo';
        $duracionMeses        = $ficha->programa?->tipoFormacion?->duracionMeses       ?? '—';

        return [
            $ficha->idFicha,
            $ficha->codigoFicha,
            $nombrePrograma,
            $codigoPrograma,
            $nombreTipoFormacion,
            $duracionMeses,
            $ficha->jornada,
            $ficha->modalidad,
            $ficha->fechaInicio,
            $ficha->fechaFin,
            $ficha->estado,
        ];
    }

    // =========================================================================
    //  NOMBRE DE PESTAÑA
    // =========================================================================

    public function title(): string
    {
        return 'Fichas';
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
