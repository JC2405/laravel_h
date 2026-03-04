<?php

namespace App\Services\Sede;

use App\Models\SedeModel;
use Illuminate\Pagination\LengthAwarePaginator;

class SedeService 
{
    public function getAll():LengthAwarePaginator
    {
      return SedeModel::with('municipio')->orderBy('idSede')->paginate(SedeModel::PAGINATION);
    }


    public function create(array $data):SedeModel
    {
      return SedeModel::create($data);  
    }


    public function update(SedeModel $sedeModel, array $data):SedeModel
    {
        $sedeModel -> update($data);
        return $sedeModel->refresh();
    }


    public function destroy(SedeModel $sedeModel):void
    {
        $sedeModel->delete();
    }
}