<?php

namespace App\Services\TipoFormacion;

use App\Models\TipoFormacionModel;
use Illuminate\Pagination\LengthAwarePaginator;

class TipoFormacionService
{
    public function getAll():LengthAwarePaginator
    {
       return TipoFormacionModel::orderBy('idTipoFormacion')->paginate(TipoFormacionModel::PAGINATOR);
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