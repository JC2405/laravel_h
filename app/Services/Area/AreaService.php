<?php

namespace App\Services\Area;

use App\Models\AreaModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;


class AreaService
{
    public function getAll():LengthAwarePaginator
    {
        return AreaModel::orderBy('idArea')->paginate(AreaModel::PAGINATION);
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