<?php

namespace App\Services\Horario;

use App\Models\AsignacionModel;
use App\Models\BloqueHorarioModel;
use Illuminate\Support\Facades\DB;

class AsignacionService
{
    public function __construct(protected DeteccionConflictoService $conflictos) {}


    public function listarClasesPorInstructor(int $idFuncionario): array
    {
        $clasesInstructor = AsignacionModel::with(['bloque.funcionario','bloque.ambiente.sede','bloque.dias','ficha.programa',
        ])
        ->whereHas('bloque', function ($q) use ($idFuncionario) {
            $q->where('idFuncionario', $idFuncionario);
        })
        ->get();

        return ['ok' => true, 'clases' => $clasesInstructor];
    }

  
    public function crearAsignacion(array $datos): array
    {
        // La fecha de inicio no puede ser posterior a la fecha de fin
        if ($datos['fecha_inicio'] > $datos['fecha_fin'])
            return ['ok' => false, 'mensaje' => 'La fecha de inicio no puede ser mayor que la fecha fin.'];

        $bloque = BloqueHorarioModel::with(['dias', 'funcionario', 'ambiente'])->find($datos['idBloque']);

        if (!$bloque)
            return ['ok' => false, 'mensaje' => 'El bloque no existe.'];

        // Evitar asignar el mismo bloque dos veces a la misma ficha en el mismo período
        $solapaFicha = AsignacionModel::where('idFicha', $datos['idFicha'])
            ->where('idBloque', $datos['idBloque'])
            ->where(fn($q) => $q
                ->where('fecha_inicio', '<=', $datos['fecha_fin'])
                ->where('fecha_fin',    '>=', $datos['fecha_inicio'])
            )
            ->first();

        if ($solapaFicha)
            return ['ok' => false, 'mensaje' => 'Esta ficha ya tiene asignado este bloque en el período indicado.'];

        $idsDias = $bloque->dias->pluck('idDia')->toArray();

        // Verificar que el instructor no tenga otra ficha diferente en el mismo horario y fechas
        $conflictosInstructor = $this->conflictos->detectarConflictoInstructorAsignacion($bloque->idFuncionario,$bloque->hora_inicio,$bloque->hora_fin,$idsDias,$datos['fecha_inicio'],$datos['fecha_fin'],excluirBloque: $bloque->idBloque,excluirFicha:  $datos['idFicha']
        );

        if ($conflictosInstructor)
            return [
                'ok'        => false,
                'codigo'    => 'CONFLICTO_INSTRUCTOR',
                'mensaje'   => 'El instructor ' . $conflictosInstructor->instructor_nombre
                             . ' ya tiene asignación de '
                             . substr($conflictosInstructor->hora_inicio, 0, 5)
                             . ' a '
                             . substr($conflictosInstructor->hora_fin, 0, 5)
                             . ' (Ficha ' . $conflictosInstructor->codigoFicha . ') — no puede tener dos fichas distintas en el mismo horario.',
                'conflicto' => $conflictosInstructor,
            ];

        // Verificar conflicto de ambiente solo si el bloque tiene ambiente asignado (presencial)
        if ($bloque->idAmbiente) {
            $conflictosAmbiente = $this->conflictos->detectarConflictoAmbienteAsignacion($bloque->idAmbiente,$bloque->hora_inicio,$bloque->hora_fin,$idsDias,$datos['fecha_inicio'],$datos['fecha_fin'],$bloque->idBloque,$datos['idFicha']);

            if ($conflictosAmbiente)
                return [
                    'ok'        => false,
                    'codigo'    => 'CONFLICTO_AMBIENTE',
                    'mensaje'   => 'El ambiente ya está ocupado de '
                                 . substr($conflictosAmbiente->hora_inicio, 0, 5)
                                 . ' a '
                                 . substr($conflictosAmbiente->hora_fin, 0, 5)
                                 . ' (Ficha ' . ($conflictosAmbiente->codigoFicha ?? '') . ') — no se puede usar el mismo ambiente para otra ficha en ese horario.',
                    'conflicto' => $conflictosAmbiente,
                ];
        }

        // Todo OK → registrar la asignación
        $asignacion = AsignacionModel::create([
            'fecha_inicio' => $datos['fecha_inicio'],
            'fecha_fin'    => $datos['fecha_fin'],
            'estado'       => $datos['estado'] ?? 'activo',
            'idBloque'     => $datos['idBloque'],
            'idFicha'      => $datos['idFicha'],
        ]);

        return [
            'ok'         => true,
            'asignacion' => $asignacion->load([
                'bloque.funcionario',
                'bloque.ambiente.sede',
                'bloque.dias',
                'ficha.programa',
            ]),
        ];
    }


    public function listarPorFicha(int $idFicha): array
    {
        $asignaciones = AsignacionModel::with(['bloque.funcionario','bloque.ambiente.sede','bloque.dias','ficha.programa',
        ])
        ->where('idFicha', $idFicha)
        ->orderByDesc('idAsignacion')
        ->get();

        return [
            'ok'           => true,
            'asignaciones' => $asignaciones,
            'grilla'       => $this->construirGrilla($asignaciones),
        ];
    }

    public function construirGrilla($asignaciones): array
    {
        // Definir franjas de 2 horas desde las 06:00 hasta las 24:00
        $slots = [];
        for ($h = 6; $h < 24; $h += 2)
            $slots[] = sprintf('%02d:00', $h) . ' - ' . sprintf('%02d:00', $h + 2);

        $grilla = array_fill_keys($slots, []); // inicializar grilla vacía con todos los slots

        foreach ($asignaciones as $asig) {
            $bloque = $asig->bloque;
            if (!$bloque) continue;

            $bloqueInicio = strtotime($bloque->hora_inicio);
            $bloqueFin    = strtotime($bloque->hora_fin);

            foreach ($slots as $slot) {
                [$desde, $hasta] = explode(' - ', $slot);
                $slotInicio = strtotime($desde);
                $slotFin    = strtotime($hasta);

                // Si el bloque no intersecta con esta franja, saltar
                if (!($bloqueInicio < $slotFin && $bloqueFin > $slotInicio)) continue;

                foreach ($bloque->dias as $dia) {
                    // No sobreescribir si ya hay datos en este slot/día (primer bloque gana)
                    if (isset($grilla[$slot][$dia->nombre])) continue;

                    $grilla[$slot][$dia->nombre] = [
                        'instructor'      => $bloque->funcionario->nombre ?? '—',
                        'ambiente'        => $bloque->ambiente
                                                ? ($bloque->ambiente->codigo . ' - No.' . $bloque->ambiente->numero)
                                                : 'Virtual',
                        'modalidad'       => $bloque->modalidad,
                        'tipoDeFormacion' => $bloque->tipoDeFormacion, // se muestra como badge en el calendario
                        'idBloque'        => $bloque->idBloque,
                        'idAsignacion'    => $asig->idAsignacion,
                    ];
                }
            }
        }

        return $grilla;
    }

  
    public function eliminarAsignacionYBloque(int $idAsignacion): array
    {
        $asignacion = AsignacionModel::find($idAsignacion);

        if (!$asignacion)
            return ['ok' => false, 'mensaje' => 'Asignación no encontrada.'];

        $idBloque = $asignacion->idBloque;

        DB::transaction(function () use ($asignacion, $idBloque) {
            $asignacion->delete();

            // Si el bloque ya no tiene más asignaciones, eliminarlo junto a sus días
            if (AsignacionModel::where('idBloque', $idBloque)->count() === 0) {
                $bloque = BloqueHorarioModel::find($idBloque);
                if ($bloque) {
                    $bloque->dias()->detach();
                    $bloque->delete();
                }
            }
        });

        return ['ok' => true, 'mensaje' => 'Asignación y horario eliminados completamente.'];
    }
}
