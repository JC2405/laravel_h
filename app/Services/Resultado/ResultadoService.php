<?php

namespace App\Services\Resultado;
use App\Models\ResultadoModel;
use Illuminate\Database\Eloquent\Collection;

class ResultadoService
{
    public function getAll(?int $idTipoFormacion = null):Collection
    {
        $query = ResultadoModel::orderBy('idResultado');
        if($idTipoFormacion !== null) {
            $query->join('competencia', 'resultado.idCompetencia', '=', 'competencia.idCompetencia')
                  ->where('competencia.idTipoFormacion', $idTipoFormacion)
                  ->select('resultado.*');
        }
        return $query->get();
    }

    public function create(array $data):ResultadoModel
    {
        return ResultadoModel::create($data);
    }

    public function update(ResultadoModel $resultadoModel,array $data)
    {
        $resultadoModel->update($data);
        return $resultadoModel->refresh();
    }


    public function delete(ResultadoModel $resultadoModel):void
    {
        $resultadoModel->delete();
    }
}