<?php

namespace App\Exports;

use App\Models\AprendizModel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * AprendicesExport
 *
 * Exporta todos los aprendices a Excel.
 *
 * Incluye los datos personales del aprendiz y la ficha
 * (grupo) a la que pertenece junto con su programa.
 *
 * Uso desde el controller:
 *   return Excel::download(new AprendicesExport, 'aprendices.xlsx');
 *
 * También puedes filtrar por ficha específica:
 *   return Excel::download(new AprendicesExport($idFicha), 'aprendices_ficha_123.xlsx');
 */
class AprendicesExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithTitle,
    ShouldAutoSize
{
    /**
     * Si se pasa un idFicha al constructor, solo se exportan
     * los aprendices de esa ficha específica.
     * Si es null, se exportan todos.
     *
     * @param  int|null $idFichaFiltro
     */
    public function __construct(
        private ?int $idFichaFiltro = null
    ) {}

    // =========================================================================
    //  DATOS
    // =========================================================================

    public function collection()
    {
        $consulta = AprendizModel::with([
            'ficha.programa', // aprendiz → ficha → programa
        ])
        ->orderBy('nombre');

        // Si se especificó una ficha, filtrar solo esa ficha
        if ($this->idFichaFiltro !== null) {
            $consulta->where('idFicha', $this->idFichaFiltro);
        }

        return $consulta->get();
    }

    // =========================================================================
    //  ENCABEZADOS
    // =========================================================================

    public function headings(): array
    {
        return [
            'ID Aprendiz',
            'Nombre Completo',
            'Documento',
            'Correo Electrónico',
            'Teléfono',
            'Estado',
            'Código Ficha',
            'Programa',
        ];
    }

    // =========================================================================
    //  MAPEO
    // =========================================================================

    /**
     * @param  AprendizModel $aprendiz
     * @return array
     */
    public function map($aprendiz): array
    {
        // Código de la ficha a la que pertenece el aprendiz
        $codigoDeLaFicha = $aprendiz->ficha?->codigoFicha ?? 'Sin ficha';

        // Nombre del programa de formación de esa ficha
        $nombreDelPrograma = $aprendiz->ficha?->programa?->nombre ?? 'Sin programa';

        return [
            $aprendiz->idAprendiz,
            $aprendiz->nombre,
            $aprendiz->documento,
            $aprendiz->correo,
            $aprendiz->telefono,
            $aprendiz->estado,
            $codigoDeLaFicha,
            $nombreDelPrograma,
        ];
    }

    // =========================================================================
    //  NOMBRE DE PESTAÑA
    // =========================================================================

    public function title(): string
    {
        return 'Aprendices';
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
