<?php

namespace App\Services\Dia;

use App\Models\DiaModel;
use Illuminate\Pagination\LengthAwarePaginator;

class DiaService
{

        public function getAll():LengthAwarePaginator
        {
            return DiaModel::orderBy('idDia')->paginate(DiaModel::PAGINATION);
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