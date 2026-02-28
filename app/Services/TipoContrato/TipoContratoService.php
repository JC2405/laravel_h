<?php

namespace App\Services\TipoContrato;
use App\Models\TipoContratoModel;


use Illuminate\Pagination\LengthAwarePaginator;

class TipoContratoService
{
    public function getAll():LengthAwarePaginator
    {
        return TipoContratoModel::orderBy('idTipoContrato')->paginate(TipoContratoModel::PAGINATION);
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
