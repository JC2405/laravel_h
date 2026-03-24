<?php

namespace App\Services\Funcionario;

use App\Models\FuncionarioModel;
use Illuminate\Pagination\LengthAwarePaginator;

class FuncionarioService
{
    public function getAll():LengthAwarePaginator
    {
        return FuncionarioModel::with(['tipoContrato', 'areas'])->orderBy('idFuncionario')->paginate(FuncionarioModel::PAGINATION);
    }

    public function create(array $data, string $documento):FuncionarioModel
    {
        // Asignar contraseña por defecto (número de documento) ANTES del INSERT
        // El modelo la hasheará automáticamente gracias al cast 'hashed'
        $data['password'] = $documento;

        $funcionario = FuncionarioModel::create($data);

        // Default rol = 2 (instructor)
        $funcionario->roles()->attach(2);

        if (isset($data['areas'])) {
            $funcionario->areas()->sync($data['areas']);
        }

        return $funcionario->load('areas');
    }

    public function show($documento)
    {
        $funcionario = FuncionarioModel::with('areas')->where('documento', $documento)->firstOrFail();
        return response()->json($funcionario);
    }

    public function update(FuncionarioModel $funcionarioModel,array $data):FuncionarioModel
    {
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $funcionarioModel->update($data);

        if (isset($data['areas'])) {
            $funcionarioModel->areas()->sync($data['areas']);
        }
        
        return $funcionarioModel->refresh()->load('areas');
    }

    public function delete(FuncionarioModel $funcionarioModel):void
    {
        $funcionarioModel->delete();
    }

    public function countInstructores(): int
    {
        return FuncionarioModel::count();
    }

}