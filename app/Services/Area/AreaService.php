<?php

namespace App\Services\Area;

use App\Models\AreaModel;
use App\Models\FuncionarioModel;

class AreaService
{
    public function getAll()
    {
        return AreaModel::orderBy('idArea')->get();
    }


    public function create(array $data):AreaModel
    {
        return AreaModel::create($data);
    }


     public function update(AreaModel $areaModel, array $data): AreaModel
    {
        $areaModel->update($data);
        return $areaModel->fresh(); 
    }

    public function delete(AreaModel $areaModel): void
    {
        $areaModel->delete();
    }
}