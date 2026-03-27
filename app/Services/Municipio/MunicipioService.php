<?php

namespace App\Services\Municipio;

use App\Models\MunicipioModel;
use Illuminate\Pagination\LengthAwarePaginator;

class MunicipioService
{
    public function getAll():LengthAwarePaginator
    {
        return MunicipioModel::orderBy('idMunicipio')->paginate(MunicipioModel::PAGINATION);
    }    


    public function create(array $data):MunicipioModel
    {
        return MunicipioModel::create($data);
    }


    public function update(MunicipioModel $municipioModel, array $data):MunicipioModel
    {
        $municipioModel->update($data);
        return $municipioModel->refresh();
    }

    public function delete(MunicipioModel $municipioModel):void
    {
        $municipioModel->delete();
    }

    /**
     * Retorna solo los municipios que tienen al menos una ficha asignada.
     * Usado por el flujo jerárquico de creación de horarios.
     */
    public function obtenerMunicipiosConFichas(): \Illuminate\Database\Eloquent\Collection
    {
        return MunicipioModel::whereHas('fichas')
            ->orderBy('nombreMunicipio')
            ->get();
    }
}