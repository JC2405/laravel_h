<?php

namespace App\Services\Ambiente;

use App\Models\AmbienteModel;
use Illuminate\Pagination\LengthAwarePaginator;

class AmbienteService
{
    public function getAll():LengthAwarePaginator
    {
        return AmbienteModel::with(['sede', 'area'])->orderBy('idAmbiente')->paginate(AmbienteModel::PAGINATION);
    }


    public function create(array $data):AmbienteModel
    {
        return AmbienteModel::create($data);
    }

    public function update(AmbienteModel $ambienteModel, array $data):AmbienteModel
    {
        $ambienteModel->update($data);
        return $ambienteModel->fresh();
    }

    public function delete(AmbienteModel $ambienteModel):void 
    {
        $ambienteModel->delete();
    }
}