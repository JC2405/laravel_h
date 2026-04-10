<?php

namespace App\Services\Ambiente;

use App\Models\AmbienteModel;

use Illuminate\Support\Facades\DB;

class AmbienteService
{
    public function getAll()
    {
        return AmbienteModel::with(['sede', 'area'])->orderBy('idAmbiente')->get();
    }

    public function countLibres(): int
    {
        $count = AmbienteModel::where('estado', 'Activo')->count();
        return $count > 0 ? $count : AmbienteModel::count();
    }

    public function ocupacion()
    {
        return DB::table('asignacion')
            ->join('ambiente', 'asignacion.idAmbiente', '=', 'ambiente.idAmbiente')
            ->select('ambiente.nombreAmbiente', DB::raw('count(asignacion.idAsignacion) as total_asignaciones'))
            ->groupBy('ambiente.nombreAmbiente')
            ->orderBy('total_asignaciones', 'desc')
            ->limit(10)
            ->get();
    }


    public function create(array $data):AmbienteModel
    {
        return AmbienteModel::create($data);
    }

    public function update(AmbienteModel $ambienteModel, array $data):AmbienteModel
    {
        $ambienteModel->update($data);
        return $ambienteModel->fresh();
    }

    public function delete(AmbienteModel $ambienteModel):void 
    {
        $ambienteModel->delete();
    }
}