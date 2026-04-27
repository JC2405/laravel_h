<?php

namespace App\Services\Funcionario;
use Illuminate\Support\Facades\DB;
use App\Models\FuncionarioModel;


use function Laravel\Prompts\select;

class FuncionarioService
{
    public function getAll()
    {
        return FuncionarioModel::with(['tipoContrato', 'areas', 'roles'])->orderBy('idFuncionario')->get();
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

    
     public function crearAdminHorarios(array $data, string $documento):FuncionarioModel
    {
        $data['password'] = $documento;
         $funcionario = FuncionarioModel::create($data);
         $funcionario->roles()->attach(1);
         
         
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


    public function verificarEstadoPorInstructor(int $idFuncionario):array
    {
        $funcionario = FuncionarioModel::find($idFuncionario);
        if(!$funcionario){
            return ['ok' => false, 'mensaje' => 'Funcionario No encontrado'];
        }
        if ($funcionario->estado != 'INACTIVO'){
            return [
                'ok' => false,
                'mensaje' => 'El funcionario debe estar Inactivo para poder eliminar el Horario.'
            ];
        }
        return['ok' => true];
    }

    public function asignarAreaMasivo($areaId, array $funcionariosIds): int
    {
        $insertedCount = 0;

        foreach ($funcionariosIds as $idFuncionario) {
            $funcionario = FuncionarioModel::find($idFuncionario);
            if ($funcionario) {
                $changes = $funcionario->areas()->syncWithoutDetaching([$areaId]);
                if (!empty($changes['attached'])) {
                    $insertedCount += count($changes['attached']);
                }
            }
        }

        return $insertedCount;
    }
 
    

    public function countHorasFuncionario(int $idFuncionario, string $fechaInicio, string $fechaFin)
    {
    return DB::table('funcionario as f')
        ->join('asignacion as asig', 'asig.idFuncionario', '=', 'f.idFuncionario')
        ->join('bloque as blq', 'blq.idAsignacion', '=', 'asig.idAsignacion')
        ->join('tipoContrato as tpc', 'tpc.idTipoContrato', '=', 'f.idTipoContrato')
        ->where('f.idFuncionario', $idFuncionario)

        // 🔥 FILTRO CORRECTO DE RANGO
        ->where(function ($q) use ($fechaInicio, $fechaFin) {
            $q->whereBetween('blq.fechaInicio', [$fechaInicio, $fechaFin])
              ->orWhereBetween('blq.fechaFin', [$fechaInicio, $fechaFin])
              ->orWhere(function ($q2) use ($fechaInicio, $fechaFin) {
                  $q2->where('blq.fechaInicio', '<=', $fechaInicio)
                     ->where('blq.fechaFin', '>=', $fechaFin);
              });
        })

        ->select(
            'f.idFuncionario',
            DB::raw("CONCAT(f.nombre, ' ', f.apellido) as nombreCompleto"),
            DB::raw("tpc.nombreTipoContrato as tipoContrato"),

            // 🔥 CÁLCULO DE HORAS
            DB::raw("
                SUM(
                    TIMESTAMPDIFF(HOUR, blq.horaInicio, blq.horaFin)
                ) as totalHoras
            ")
        )
        ->groupBy('f.idFuncionario', 'f.nombre', 'f.apellido', 'tpc.nombreTipoContrato')
        ->first();
    }
}