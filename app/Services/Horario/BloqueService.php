<?php

namespace App\Services\Horario;

use App\Models\BloqueHorarioModel;
use App\Services\Horario\DeteccionConflictoService;
use Illuminate\Support\Facades\DB;

class BloqueService
{
    public function __construct(protected DeteccionConflictoService $conflictos) {}

    public function crearBloque(array $datos): array
    {
        $modalidad = strtolower(trim($datos['modalidad'] ?? ''));
        if ($datos['hora_inicio'] >= $datos['hora_fin'])
            return ['ok' => false, 'codigo' => 'HORA_INVALIDA', 'mensaje' => 'La hora inicio debe ser menor a la hora fin.'];
        if ($modalidad === 'presencial' && empty($datos['idAmbiente']))
            return ['ok' => false, 'codigo' => 'AMBIENTE_REQUERIDO', 'mensaje' => 'El ambiente es requerido para la modalidad presencial.'];

         //verifica que el instructor no tenga otro bloque
        $validacionInstructor = $this->conflictos->detectarConfictoInstructor($datos['idFuncionario'],$datos['hora_inicio'],$datos['hora_fin'],$datos['dias'],excluirBloque: $datos['idBloque'] ?? null ); // excluye el bloque actual 

        if ($validacionInstructor)
            return [
                'ok'        => false,
                'codigo'    => 'CONFLICTO_INSTRUCTOR',
                'mensaje'   => 'El instructor ' . $validacionInstructor->instructor_nombre
                             . ' ya tiene clase de '
                             . substr($validacionInstructor->hora_inicio, 0, 5)
                             . ' a '
                             . substr($validacionInstructor->hora_fin, 0, 5)
                             . ' los días seleccionados.',
                'conflicto' => $validacionInstructor,
            ];

        // Solo verificar conflicto de ambiente si la modalidad es presencial
        if ($modalidad === 'presencial') {
            $validacionAmbiente = $this->conflictos->detectarConflictoAmbiente($datos['idAmbiente'],$datos['hora_inicio'],$datos['hora_fin'],$datos['dias'],excluirFicha: $datos['idFicha'] ?? null // permite agregar más clases a la misma ficha en ese ambiente
            );

            if ($validacionAmbiente)
                return [
                    'ok'        => false,
                    'codigo'    => 'CONFLICTO_AMBIENTE',
                    'mensaje'   => 'El ambiente ya está ocupado de '
                                 . substr($validacionAmbiente->hora_inicio, 0, 5)
                                 . ' a '
                                 . substr($validacionAmbiente->hora_fin, 0, 5)
                                 . ' los días seleccionados.',
                    'conflicto' => $validacionAmbiente,
                ];
        }

        return DB::transaction(function () use ($datos, $modalidad) {
            $bloque = BloqueHorarioModel::create([
                'hora_inicio'     => $datos['hora_inicio'],
                'hora_fin'        => $datos['hora_fin'],
                'modalidad'       => $modalidad,
                'idAmbiente'      => $modalidad === 'presencial' ? ($datos['idAmbiente'] ?? null) : null,
                'idFuncionario'   => $datos['idFuncionario'],
                'tipoDeFormacion' => $datos['tipoDeFormacion'] ?? null,
            ]);
            $bloque->dias()->attach($datos['dias']); // relacionar los días seleccionados
            return ['ok' => true, 'bloque' => $bloque->load(['funcionario', 'ambiente', 'dias'])];
        });
    }

    public function eliminarDiaDeBloque(int $idBloque, int $idDia): array
    {
        $bloque = BloqueHorarioModel::with('dias')->find($idBloque);

        if (!$bloque)
            return ['ok' => false, 'mensaje' => 'Bloque no encontrado.'];

        if (!$bloque->dias->contains('idDia', $idDia))
            return ['ok' => false, 'mensaje' => 'El día no está asignado a este bloque.'];

        // Verificar que no sea el único día antes de eliminar
        if ($bloque->dias->count() === 1)
            return ['ok' => false, 'codigo' => 'ULTIMO_DIA', 'mensaje' => 'No se puede eliminar el único día del bloque. Elimina el bloque completo.'];

        DB::transaction(fn() => $bloque->dias()->detach($idDia));

        return [
            'ok'      => true,
            'mensaje' => 'Día eliminado del bloque correctamente.',
            'bloque'  => $bloque->fresh()->load(['funcionario', 'ambiente', 'dias']),
        ];
    }


    public function eliminarBloque(int $idBloque): array
    {
        $bloque = BloqueHorarioModel::find($idBloque);

        if (!$bloque)
            return ['ok' => false, 'mensaje' => 'Bloque no encontrado.'];

        DB::transaction(function () use ($bloque) {
            $bloque->dias()->detach(); // desvincula los días antes de borrar el bloque
            $bloque->delete();
        });

        return ['ok' => true, 'mensaje' => 'Bloque eliminado correctamente.'];
    }
}