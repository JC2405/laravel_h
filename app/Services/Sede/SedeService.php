<?php

namespace App\Services\Sede;

use App\Models\SedeModel;


class SedeService 
{
    public function getAll()
    {
      return SedeModel::with('municipio')->orderBy('idSede')->get();
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