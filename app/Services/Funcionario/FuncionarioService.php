<?php

namespace App\Services\Funcionario;

use App\Models\FuncionarioModel;
use Illuminate\Pagination\LengthAwarePaginator;

class FuncionarioService
{
    public function getAll():LengthAwarePaginator
    {
        return FuncionarioModel::orderBy('idFuncionario')->paginate(FuncionarioModel::PAGINATION);
    }

    public function create(array $data):FuncionarioModel
    {
        return FuncionarioModel::create($data);
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