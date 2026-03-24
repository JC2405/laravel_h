<?php

namespace App\Services\Ficha;

use App\Models\FichaModel;

class FichaService
{
    public function getAll()
    {
        return FichaModel::with([
            'programa.tipoFormacion',
            'asignaciones.ambiente.sede',
        ])->orderBy('idFicha')->get();
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
        return FichaModel::where('codigoFicha', $codigoFicha)->firstOrFail();
    }

    public function countActivas(): int
    {
        $count = FichaModel::where('estado', '!=', 'Inactivo')->count();
        return $count > 0 ? $count : FichaModel::count();
    }
}