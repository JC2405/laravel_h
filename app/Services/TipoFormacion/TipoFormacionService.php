<?php

namespace App\Services\TipoFormacion;

use App\Models\TipoFormacionModel;


class TipoFormacionService
{
    public function getAll()
    {
       return TipoFormacionModel::orderBy('idTipoFormacion')->get();
    }


    public function create(array $data):TipoFormacionModel
    {
        return TipoFormacionModel::create($data);
    }

    public function update(TipoFormacionModel $tipoFormacionModel, array $data):TipoFormacionModel
    {
        $tipoFormacionModel->update($data);
        return $tipoFormacionModel->refresh();
    }


    public function delete(TipoFormacionModel $tipoFormacionModel):void
    {
        $tipoFormacionModel->delete();
    }
}