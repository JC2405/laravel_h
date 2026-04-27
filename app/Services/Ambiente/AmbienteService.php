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

    public function buscarAmbiente(int $idSede, string $fechaInicio, string $fechaFin, string $horaInicio, string $horaFin): array
{
    return DB::table('ambiente as a')
        ->join('area as ar', 'a.idArea', '=', 'ar.idArea') 

        ->where('a.idSede', $idSede)
        ->where('a.estado', 'Activo')

        ->whereNotExists(function ($query) use ($fechaInicio, $fechaFin, $horaInicio, $horaFin) {
            $query->select(DB::raw(1))
                ->from('asignacion as asg')
                ->join('bloque as b', 'asg.idAsignacion', '=', 'b.idAsignacion')
                ->whereColumn('asg.idAmbiente', 'a.idAmbiente')

                ->where(function ($q) use ($fechaInicio, $fechaFin) {
                    $q->where('b.fechaInicio', '<=', $fechaFin)
                      ->where('b.fechaFin', '>=', $fechaInicio);
                })

                ->where(function ($q) use ($horaInicio, $horaFin) {
                    $q->where('b.horaInicio', '<', $horaFin)
                      ->where('b.horaFin', '>', $horaInicio);
                });
        })

        // 🔥 AQUÍ ARMAS EL TEXTO
        ->select(
            'a.idAmbiente',
            DB::raw("CONCAT(ar.nombreArea, ' - ', a.bloque) as nombreCompleto")
        )

        ->get()
        ->toArray();
    }

}