<?php

namespace App\Services\Programa;
use App\Models\ProgramaModel;
use Illuminate\Database\Eloquent\Collection;

class ProgramaService
{
    public function getAll():Collection
    {
        return ProgramaModel::with('tipoFormacion')->orderBy('idPrograma')->get();
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