<?php

namespace App\Services\Ficha;

use App\Models\FichaModel;
use Illuminate\Pagination\LengthAwarePaginator;

class FichaService
{
    public function getAll():LengthAwarePaginator
    {
        return FichaModel::orderBy('idFicha')->paginate(FichaModel::PAGINATION);
    }


    public function create(array $data):FichaModel
    {
        return FichaModel::create($data);
    }


    public function update(FichaModel $fichaModel,$data):FichaModel
    {
        $fichaModel->update($data);
        return $fichaModel->fresh();

    }

    public function delete(FichaModel $fichaModel):void
    {
        $fichaModel->delete();
    }


    public function show($codigoFicha)
    {
        $ficha = FichaModel::where('codigoFicha',$codigoFicha)->firstOrFail();
        return response()->json($ficha);
    }
}