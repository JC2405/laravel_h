<?php

namespace App\Services\Aprendiz;

use App\Models\AprendizModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


class AprendizService
{
   public function getAll(?string $idFicha = null)
{
    $query = AprendizModel::with('ficha')->orderBy('idAprendiz');

    if ($idFicha) {
        $query->where('idFicha', $idFicha);
    }

    return $query->get();
}

    public function store(array $data, string $documento): AprendizModel
    {
        $data['password'] = Hash::make($documento); // hashear el documento como contraseña
        $aprendiz = AprendizModel::create($data);
        return $aprendiz;
    }

    public function update(AprendizModel $aprendizModel, $data):AprendizModel
    {
        $aprendizModel->update($data);
        return $aprendizModel->refresh();
    }

    public function delete(AprendizModel $aprendizModel):void
    {
        $aprendizModel->delete();
    }

    public function show($documento)
    {
        return AprendizModel::where('documento',$documento)->firstOrFail();
    }

    public function countMatriculados(): int
    {
        return AprendizModel::count();
    }

    public function countByPrograma()
    {
        return DB::table('aprendiz')
            ->join('ficha', 'aprendiz.idFicha', '=', 'ficha.idFicha')
            ->join('programa', 'ficha.idPrograma', '=', 'programa.idPrograma')
            ->select('programa.nombrePrograma', DB::raw('count(aprendiz.idAprendiz) as total'))
            ->groupBy('programa.nombrePrograma')
            ->get();
    }
}