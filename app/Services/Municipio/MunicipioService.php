<?php

namespace App\Services\Municipio;

use App\Models\MunicipioModel;

class MunicipioService
{
    public function getAll()
    {
        return MunicipioModel::orderBy('idMunicipio')->get();
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