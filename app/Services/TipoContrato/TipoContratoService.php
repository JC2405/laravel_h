<?php

namespace App\Services\TipoContrato;
use App\Models\TipoContratoModel;



class TipoContratoService
{
    public function getAll()
    {
        return TipoContratoModel::orderBy('idTipoContrato')->get();
    }


    public function create(array $data):TipoContratoModel
    {
        return TipoContratoModel::create($data);
    }

    public function update(TipoContratoModel $tipoContratoModel, array $data):TipoContratoModel
    {
        $tipoContratoModel->update($data);
        return $tipoContratoModel->fresh();
    }


    public function delete(TipoContratoModel $tipoContratoModel): void
    {
        $tipoContratoModel->delete();
    }
}
