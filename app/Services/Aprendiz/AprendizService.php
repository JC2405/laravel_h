<?php

namespace App\Services\Aprendiz;

use App\Models\AprendizModel;
use Illuminate\Pagination\LengthAwarePaginator;

class AprendizService
{
    public function getAll():LengthAwarePaginator
    {
        return AprendizModel::orderBy('idAprendiz')->paginate(AprendizModel::PAGINATION);
    }

    public function store(array $data):AprendizModel
    {
        return AprendizModel::create($data);
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
        $aprendiz = AprendizModel::where('documento',$documento)->firstOrFail();
        return response()->json($aprendiz);
    }
}