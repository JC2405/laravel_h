<?php

namespace App\Services\Funcionario;

use App\Models\FuncionarioModel;
use Illuminate\Pagination\LengthAwarePaginator;

class FuncionarioService
{
    public function getAll():LengthAwarePaginator
    {
        return FuncionarioModel::with('tipoContrato')->orderBy('idFuncionario')->paginate(FuncionarioModel::PAGINATION);
    }

    public function create(array $data):FuncionarioModel
    {
        $funcionario = FuncionarioModel::create($data);

        // Asignar rol de instructor (idRol = 2) en funcionario_rol
        $funcionario->roles()->attach(2);

        return $funcionario;
    }

    public function show($documento)
    {
    $funcionario = FuncionarioModel::where('documento', $documento)->firstOrFail();
    return response()->json($funcionario);
    }

    public function update(FuncionarioModel $funcionarioModel,array $data):FuncionarioModel
    {
        $funcionarioModel->update($data);
        return $funcionarioModel->refresh();
    }

    public function delete(FuncionarioModel $funcionarioModel):void
    {
        $funcionarioModel->delete();
    }

}