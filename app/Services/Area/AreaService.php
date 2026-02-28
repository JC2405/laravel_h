<?php

namespace App\Services\Area;

use App\Models\AreaModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;


class AreaService
{
    public function getAll():LengthAwarePaginator
    {
        return AreaModel::latest()->paginate(AreaModel::PAGINATION);
    }


    public function create(array $data):AreaModel
    {
        return AreaModel::create($data);
    }
}