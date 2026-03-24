<?php

namespace App\Services\Aprendiz;

use App\Models\AprendizModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AprendizService
{
    public function getAll():LengthAwarePaginator
    {
        return AprendizModel::with('ficha')->orderBy('idAprendiz')->paginate(AprendizModel::PAGINATION);
    }

    public function store(array $data, string $documento):AprendizModel
    {
        $data['password'] = $documento;

        $aprendiz = AprendizModel::create($data);
        

        return $aprendiz ;
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