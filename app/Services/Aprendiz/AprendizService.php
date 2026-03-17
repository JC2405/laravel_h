<?php

namespace App\Services\Aprendiz;

use App\Models\AprendizModel;
use Illuminate\Pagination\LengthAwarePaginator;

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
}