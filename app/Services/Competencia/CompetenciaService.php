<?php

namespace App\Services\Competencia;
use App\Models\CompetenciaModel;
use Illuminate\Database\Eloquent\Collection;



class CompetenciaService
{
    public function getAll(): Collection
    {
        return CompetenciaModel::orderBy('idCompetencia')->get();
    }


    public function create(array $data):CompetenciaModel
    {
        return CompetenciaModel::create($data);
    }


     public function update(CompetenciaModel $competenciaModel, array $data): CompetenciaModel
    {
        $competenciaModel->update($data);
        return $competenciaModel->fresh(); 
    }

    public function delete(CompetenciaModel $competenciaModel): void
    {
        $competenciaModel->delete();
    }
}