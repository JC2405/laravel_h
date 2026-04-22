<?php

namespace App\Services\Programa;
use App\Models\ProgramaModel;
use Illuminate\Database\Eloquent\Collection;

class ProgramaService
{
    public function getAll():Collection
    {
        return ProgramaModel::with('tipoFormacion')->orderBy('idPrograma')->get();
    }

        public function create(array $data): ProgramaModel
    {
        if (ProgramaModel::where('codigo', $data['codigo'])->exists()) {
            throw new \Exception('El código de programa "' . $data['codigo'] . '" ya está registrado.');
        }
        return ProgramaModel::create($data);
    }
    
    public function update(ProgramaModel $programaModel, array $data)
    {
        if (ProgramaModel::where('codigo', $data['codigo'])
            ->where('idPrograma', '!=', $programaModel->idPrograma)
            ->exists()) {
            throw new \Exception('El código de programa "' . $data['codigo'] . '" ya está en uso por otro programa.');
        }
        $programaModel->update($data);
        return $programaModel->refresh();
    }


    public function delete(ProgramaModel $programaModel):void
    {
        $programaModel->delete();
    }
}