<?php

namespace App\Exports;

use App\Models\FuncionarioModel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * FuncionariosExport
 *
 * Exporta todos los instructores/funcionarios a un archivo Excel.
 *
 * Cada fila del Excel representa un funcionario con sus datos básicos,
 * su tipo de contrato y las áreas de formación que tiene asignadas.
 *
 * Uso desde el controller:
 *   return Excel::download(new FuncionariosExport, 'funcionarios.xlsx');
 */
class FuncionariosExport implements
    FromCollection,   // indica que los datos vienen de una colección Eloquent
    WithHeadings,     // agrega la fila de encabezados (títulos de columnas)
    WithMapping,      // transforma cada modelo en un array de valores para la fila
    WithStyles,       // aplica estilos visuales a la hoja
    WithTitle,        // nombre de la pestaña del Excel
    ShouldAutoSize    // ajusta automáticamente el ancho de las columnas
{
    // =========================================================================
    //  DATOS
    // =========================================================================

    /**
     * Retorna todos los funcionarios con sus relaciones cargadas.
     *
     * Usamos eager loading (with) para evitar el problema N+1:
     * sin esto, cada funcionario haría una consulta extra para obtener
     * su tipo de contrato y sus áreas.
     */
    public function collection()
    {
        return FuncionarioModel::with([
            'tipoContrato', // para mostrar el nombre del contrato en lugar del ID
            'areas',        // para listar las áreas separadas por coma
            'roles',        // para mostrar el rol del funcionario
        ])
        ->orderBy('nombre') // orden alfabético
        ->get();
    }

    // =========================================================================
    //  ENCABEZADOS DE COLUMNAS
    // =========================================================================

    /**
     * Define los títulos de la primera fila del Excel.
     *
     * El orden DEBE coincidir exactamente con el orden de valores
     * que retorna el método map() más abajo.
     */
    public function headings(): array
    {
        return [
            'ID',
            'Nombre Completo',
            'Documento',
            'Correo Electrónico',
            'Teléfono',
            'Estado',
            'Tipo de Contrato',
            'Áreas Asignadas',
            'Rol',
        ];
    }

    // =========================================================================
    //  MAPEO DE DATOS (modelo → fila de Excel)
    // =========================================================================

    /**
     * Transforma un FuncionarioModel en el array de valores que irá
     * en una fila del Excel.
     *
     * @param  FuncionarioModel $funcionario  Instancia del modelo a convertir
     * @return array                          Valores en el mismo orden que headings()
     */
    public function map($funcionario): array
    {
        // Obtener el nombre del tipo de contrato o "Sin asignar" si no tiene
        $nombreTipoContrato = $funcionario->tipoContrato?->nombreTipoContrato ?? 'Sin asignar';

        // Unir los nombres de todas las áreas en una sola cadena separada por coma
        // Ejemplo: "Sistemas, Electricidad, Diseño"
        $areasComoTexto = $funcionario->areas->pluck('nombreArea')->join(', ');
        $areasComoTexto = $areasComoTexto ?: 'Sin áreas';

        // Obtener el nombre del primer rol del funcionario
        $nombreRol = $funcionario->roles->first()?->nombreRol ?? 'Sin rol';

        return [
            $funcionario->idFuncionario,
            $funcionario->nombre,
            $funcionario->documento,
            $funcionario->correo,
            $funcionario->telefono,
            $funcionario->estado,
            $nombreTipoContrato,
            $areasComoTexto,
            $nombreRol,
        ];
    }

    // =========================================================================
    //  NOMBRE DE LA PESTAÑA
    // =========================================================================

    /**
     * Nombre que aparece en la pestaña (tab) del archivo Excel.
     */
    public function title(): string
    {
        return 'Instructores';
    }

    // =========================================================================
    //  ESTILOS VISUALES
    // =========================================================================

    /**
     * Aplica estilos a la hoja de cálculo.
     *
     * Solo se aplica un estilo visual a la fila de encabezados:
     * fondo verde oscuro (color SENA) y texto blanco en negrita.
     */
    public function styles(Worksheet $hoja): array
    {
        return [
            // La fila 1 es siempre la fila de encabezados
            1 => [
                'font' => [
                    'bold'  => true,       // texto en negrita
                    'color' => ['argb' => 'FFFFFFFF'], // texto blanco
                ],
                'fill' => [
                    'fillType'   => 'solid',
                    'startColor' => ['argb' => 'FF39A900'], // verde SENA
                ],
            ],
        ];
    }
}
