<?php

namespace App\Services\Competencia;
use App\Models\CompetenciaModel;
use Illuminate\Database\Eloquent\Collection;



class CompetenciaService
{
    public function getAll(?int $idTipoFormacion = null): Collection
    {
        $query = CompetenciaModel::orderBy('idCompetencia');
        if ($idTipoFormacion !== null) {
            $query->where('idTipoFormacion', $idTipoFormacion);
        }
        return $query->get();
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