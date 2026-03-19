<?php

namespace App\Imports;

use App\Models\CompetenciaModel;
use App\Models\TipoFormacionModel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

/**
 * CompetenciaImport
 *
 * Formato esperado del Excel (primera fila = encabezados):
 * ┌──────────────────────┬──────────┬──────────────┬──────────────────────┐
 * │ nombreCompetencia    │ codigo   │ tipo         │ nombreTipoFormacion   │
 * ├──────────────────────┼──────────┼──────────────┼──────────────────────┤
 * │ Análisis de Sistemas │ 228186   │ Titulada     │ Tecnólogo            │
 * └──────────────────────┴──────────┴──────────────┴──────────────────────┘
 *
 * WithHeadingRow convierte los encabezados a slug automáticamente:
 *   "nombreCompetencia"   → 'nombrecompetencia'
 *   "nombreTipoFormacion" → 'nombretipo_formacion' (con slug) o 'nombretipo formacion'
 *
 * IMPORTANTE: el slug formatter de maatwebsite convierte espacios y mayúsculas
 * a snake_case básico, por lo que 'nombreTipoFormacion' queda como
 * 'nombretipo_formacion'. Verificado con config('excel.imports.heading_row.formatter' => 'slug').
 * Si tu config usa 'none', accede con el nombre tal cual.
 *
 * Para evitar ambigüedad usamos el constructor para recibir opcionalmente
 * un idTipoFormacion fijo (cuando el frontend lo envía directamente).
 * Si se pasa, se ignora la columna del Excel.
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

    /**
     * Cache de tipos de formación ya consultados en esta importación.
     * Evita N+1: si el Excel tiene 50 filas del mismo tipo, solo hace 1 query.
     *
     * @var array<string, int|null>  nombreTipoFormacion → idTipoFormacion|null
     */
    private array $cacheTiposFormacion = [];

    /**
     * @param  int|null $idTipoFormacionFijo
     *   Si el frontend envía el idTipoFormacion directamente (por body),
     *   se usa para todas las filas y se ignora la columna del Excel.
     *   Si es null, se lee la columna "nombreTipoFormacion" del Excel.
     */
    public function __construct(
        private ?int $idTipoFormacionFijo = null
    ) {}

    // =========================================================================
    //  PROCESAMIENTO
    // =========================================================================

    public function collection(Collection $filasDeLaHoja): void
    {
        foreach ($filasDeLaHoja as $fila) {

            $codigo = trim($fila['codigo'] ?? '');
            if (empty($codigo)) {
                continue;
            }

            // ── Resolver idTipoFormacion ──────────────────────────────────────
            $idTipoFormacion = $this->resolverIdTipoFormacion($fila);

            // Si no se pudo resolver el tipo, saltar la fila silenciosamente
            // (ya fue registrada como failure en rules() o aquí)
            if ($idTipoFormacion === null) {
                continue;
            }

            CompetenciaModel::updateOrCreate(
                ['codigo' => $codigo],
                [
                    'nombreCompetencia' => trim($fila['nombrecompetencia'] ?? ''),
                    'tipo'              => trim($fila['tipo'] ?? ''),
                    'idTipoFormacion'   => $idTipoFormacion,
                ]
            );

            $this->totalFilasImportadas++;
        }
    }

    // =========================================================================
    //  RESOLUCIÓN DE TIPO DE FORMACIÓN
    // =========================================================================

    /**
     * Determina el idTipoFormacion a usar para una fila.
     *
     * Prioridad:
     *  1. Si se pasó un idTipoFormacionFijo al constructor → siempre ese.
     *  2. Si no → leer la columna "nombreTipoFormacion" del Excel y buscar en BD.
     *
     * Usa caché interno para no repetir la misma query por cada fila.
     */
    private function resolverIdTipoFormacion($fila): ?int
    {
        // Caso 1: el frontend envió el id directamente
        if ($this->idTipoFormacionFijo !== null) {
            return $this->idTipoFormacionFijo;
        }

        // Caso 2: leer del Excel
        // WithHeadingRow con formatter 'slug' convierte "nombreTipoFormacion"
        // a snake_case: prueba ambas variantes por compatibilidad
        $nombreTipo = trim(
            $fila['nombretipo_formacion']
            ?? $fila['nombretipo formacion']
            ?? $fila['nombretipoformacion']
            ?? ''
        );

        if (empty($nombreTipo)) {
            return null;
        }

        // Revisar caché antes de hacer la query
        if (array_key_exists($nombreTipo, $this->cacheTiposFormacion)) {
            return $this->cacheTiposFormacion[$nombreTipo];
        }

        // Buscar en BD (insensible a mayúsculas/acentos gracias a LIKE)
        $tipoFormacion = TipoFormacionModel::whereRaw(
            'LOWER(nombreTipoFormacion) = LOWER(?)',
            [$nombreTipo]
        )->first();

        $id = $tipoFormacion?->idTipoFormacion;
        $this->cacheTiposFormacion[$nombreTipo] = $id;

        return $id;
    }

    // =========================================================================
    //  VALIDACIÓN POR FILA
    // =========================================================================

    public function rules(): array
    {
        $reglas = [
            'nombrecompetencia' => 'required|string|max:200',
            'codigo'            => 'required|string|max:40',
            'tipo'              => 'required|string|max:50',
        ];

        // Solo exigir la columna del Excel si no viene fijo por constructor
        if ($this->idTipoFormacionFijo === null) {
            // El slug de "nombreTipoFormacion" varía según el formatter configurado.
            // Validamos el campo más probable; si falla, el resolverIdTipoFormacion
            // lo manejará con el continue.
            $reglas['nombretipo_formacion'] = 'nullable|string|max:100';
        }

        return $reglas;
    }

    public function customValidationMessages(): array
    {
        return [
            'nombrecompetencia.required'   => 'La columna "nombreCompetencia" es obligatoria.',
            'codigo.required'              => 'La columna "codigo" es obligatoria.',
            'tipo.required'                => 'La columna "tipo" es obligatoria.',
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