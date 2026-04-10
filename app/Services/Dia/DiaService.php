<?php

namespace App\Services\Dia;

use App\Models\DiaModel;

class DiaService
{

        public function getAll()
        {
            return DiaModel::orderBy('idDia')->get();
        }
    

        public function create(array $data):DiaModel
        {
            return DiaModel::create($data);
        }


        public function update(DiaModel $diaModel, array $data)
        {
            $diaModel ->update($data);
            return $diaModel->fresh();
        }

        public function delete(DiaModel $diaModel):void
        {
            $diaModel->delete();
        }
}