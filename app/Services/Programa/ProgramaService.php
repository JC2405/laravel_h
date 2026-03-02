<?php

namespace App\Services\Programa;
use App\Models\ProgramaModel;
use Illuminate\Pagination\LengthAwarePaginator;

class ProgramaService
{
    public function getAll():LengthAwarePaginator
    {
        return ProgramaModel::orderBy('idPrograma')->paginate(ProgramaModel::PAGINATION);
    }

    public function create(array $data):ProgramaModel
    {
        return ProgramaModel::create($data);
    }

    public function update(ProgramaModel $programaModel,array $data)
    {
        $programaModel->update($data);
        return $programaModel->refresh();
    }


    public function delete(ProgramaModel $programaModel):void
    {
        $programaModel->delete();
    }
}