<?php

namespace App\Services\Ficha;

use App\Models\FichaModel;

class FichaService
{
    public function getAll()
    {
        return FichaModel::with([
            'programa.tipoFormacion',
            'asignaciones.ambiente.sede',
            'municipio',
        ])->orderBy('idFicha')->get();
    }

    public function countFichasPorProgramaYMunicipio($idMunicipio)
    {
        return FichaModel::select('idPrograma')
            ->where('idMunicipio', $idMunicipio)
            ->selectRaw('COUNT(*) as total')
            ->groupBy('idPrograma')
            ->with('programa')
            ->get();
    }


    public function create(array $data):FichaModel
    {
        return FichaModel::create($data);
    }


    public function update(FichaModel $fichaModel,$data):FichaModel
    {
        $fichaModel->update($data);
        return $fichaModel->fresh();

    }

    public function delete(FichaModel $fichaModel):void
    {
        $fichaModel->delete();
    }


    public function show($codigoFicha)
    {
        return FichaModel::where('codigoFicha', $codigoFicha)->firstOrFail();
    }

    public function countActivas(): int
    {
        $count = FichaModel::where('estado', '!=', 'Inactivo')->count();
        return $count > 0 ? $count : FichaModel::count();
    }

    // ── Flujo jerárquico: Municipio → Programa → Ficha ────────────────────────

    /**
     * Retorna los programas distintos que tienen fichas en el municipio indicado.
     */
    public function obtenerProgramasPorMunicipio(int $idMunicipio): \Illuminate\Support\Collection
    {
        return FichaModel::with('programa')
            ->where('idMunicipio', $idMunicipio)
            ->get()
            ->pluck('programa')
            ->filter()                 // elimina nulls
            ->unique('idPrograma')
            ->values();
    }

    /**
     * Retorna las fichas activas que corresponden al programa y municipio dados.
     */
    public function obtenerFichasPorProgramaMunicipio(int $idPrograma, int $idMunicipio): \Illuminate\Database\Eloquent\Collection
    {
        return FichaModel::with(['programa.tipoFormacion', 'municipio', 'asignaciones'])
            ->where('idPrograma',  $idPrograma)
            ->where('idMunicipio', $idMunicipio)
            ->where('estado',      'Activo')
            ->get();
    }
}