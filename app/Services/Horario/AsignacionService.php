<?php

namespace App\Services\Horario;

use App\Models\AsignacionModel;
use App\Models\BloqueHorarioModel;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AsignacionService
{
    public function __construct(
        protected DeteccionConflictoService $conflictos,
        protected BloqueService $bloques,
    ) {}

    public function crearAsignacion(array $datos): array
    {
        // 1. Fechas coherentes (fuera de la transacción, no toca BD)
        if ($datos['fecha_inicio'] > $datos['fecha_fin']) {
            return ['ok' => false, 'mensaje' => 'La fecha de inicio no puede ser mayor que la fecha fin.'];
        }

        try {
            $asignacion = DB::transaction(function () use ($datos) {

                // ── Resolvemos el bloque ──────────────────────────────────────────────
                if (!empty($datos['idBloque'])) {

                    $bloque = BloqueHorarioModel::with(['dias', 'funcionario', 'ambiente'])
                        ->find($datos['idBloque']);

                    if (!$bloque) {
                        throw new RuntimeException('El bloque no existe.');
                    }

                } else {

                    // 2. Crear bloque dentro de la transacción
                    // Si algo falla después, el rollback lo borra automáticamente
                    $resultadoBloque = $this->bloques->crearBloqueSinValidarConflictos($datos);

                    if (!$resultadoBloque['ok']) {
                        throw new RuntimeException($resultadoBloque['mensaje']);
                    }

                    $bloque = $resultadoBloque['bloque'];
                }

                // 3. No asignar el mismo bloque dos veces a la misma ficha en el mismo período
                $solapaFicha = AsignacionModel::where('idFicha', $datos['idFicha'])
                    ->where('idBloque', $bloque->idBloque)
                    ->where(fn($q) =>
                        $q->where('fecha_inicio', '<=', $datos['fecha_fin'])
                          ->where('fecha_fin',    '>=', $datos['fecha_inicio'])
                    )
                    ->first();

                if ($solapaFicha) {
                    throw new RuntimeException('Esta ficha ya tiene asignado este bloque en el período indicado.');
                }

                $idsDias = $bloque->dias->pluck('idDia')->toArray();

                // 4. Conflicto de instructor: mismo horario + días + fechas
                $conflictoInstructor = $this->conflictos->detectarConflictoInstructorAsignacion(
                    $bloque->idFuncionario,
                    $bloque->hora_inicio,
                    $bloque->hora_fin,
                    $idsDias,
                    $datos['fecha_inicio'],
                    $datos['fecha_fin'],
                    excluirBloque: $bloque->idBloque,
                    excluirFicha:  $datos['idFicha']
                );

                if ($conflictoInstructor) {
                    throw new RuntimeException(
                        'El instructor ' . $conflictoInstructor->instructor_nombre
                        . ' ya tiene asignación de '
                        . substr($conflictoInstructor->hora_inicio, 0, 5)
                        . ' a ' . substr($conflictoInstructor->hora_fin, 0, 5)
                        . ' (Ficha ' . $conflictoInstructor->codigoFicha . ')'
                        . ' — no puede tener dos fichas en el mismo horario.',
                        409
                    );
                }

                // 5. Conflicto de ambiente: mismo horario + días + fechas
                if ($bloque->idAmbiente) {

                    $conflictoAmbiente = $this->conflictos->detectarConflictoAmbienteAsignacion(
                        $bloque->idAmbiente,
                        $bloque->hora_inicio,
                        $bloque->hora_fin,
                        $idsDias,
                        $datos['fecha_inicio'],
                        $datos['fecha_fin'],
                        excluirBloque: $bloque->idBloque,
                        excluirFicha:  $datos['idFicha']
                    );

                    if ($conflictoAmbiente) {
                        throw new RuntimeException(
                            'El ambiente ya está ocupado de '
                            . substr($conflictoAmbiente->hora_inicio, 0, 5)
                            . ' a ' . substr($conflictoAmbiente->hora_fin, 0, 5)
                            . ' (Ficha ' . ($conflictoAmbiente->codigoFicha ?? '') . ')'
                            . ' — no se puede usar el mismo ambiente para otra ficha en ese horario.',
                            409
                        );
                    }
                }

                // 6. Todo OK → crear la asignación
                return AsignacionModel::create([
                    'fecha_inicio' => $datos['fecha_inicio'],
                    'fecha_fin'    => $datos['fecha_fin'],
                    'estado'       => $datos['estado'] ?? 'activo',
                    'idBloque'     => $bloque->idBloque,
                    'idFicha'      => $datos['idFicha'],
                ])->load([
                    'bloque.funcionario',
                    'bloque.ambiente.sede',
                    'bloque.dias',
                    'ficha.programa',
                ]);
            });

            return ['ok' => true, 'asignacion' => $asignacion];

        } catch (RuntimeException $e) {
            // Rollback ya ejecutado por Laravel — devolvemos el mensaje de validación
            return [
                'ok'      => false,
                'codigo'  => $e->getCode() === 409 ? 'CONFLICTO' : 'ERROR',
                'mensaje' => $e->getMessage(),
            ];
        } catch (\Throwable $e) {
            // Error inesperado de BD u otro
            return [
                'ok'      => false,
                'codigo'  => 'ERROR_INTERNO',
                'mensaje' => 'Ocurrió un error inesperado al crear la asignación.',
            ];
        }
    }

   

    public function listarPorFicha(int $idFicha): array
    {
        $asignaciones = AsignacionModel::with([
                'bloque.funcionario',
                'bloque.ambiente.sede',
                'bloque.dias',
                'ficha.programa',
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
        $slots = [];
        for ($h = 6; $h < 24; $h += 2) {
            $slots[] = sprintf('%02d:00', $h) . ' - ' . sprintf('%02d:00', $h + 2);
        }

        $grilla = array_fill_keys($slots, []);

        foreach ($asignaciones as $asig) {
            $bloque = $asig->bloque;
            if (!$bloque) continue;

            $bloqueInicio = strtotime($bloque->hora_inicio);
            $bloqueFin    = strtotime($bloque->hora_fin);

            foreach ($slots as $slot) {
                [$desde, $hasta] = explode(' - ', $slot);
                $slotInicio = strtotime($desde);
                $slotFin    = strtotime($hasta);

                if (!($bloqueInicio < $slotFin && $bloqueFin > $slotInicio)) continue;

                foreach ($bloque->dias as $dia) {
                    if (isset($grilla[$slot][$dia->nombre])) continue;

                    $grilla[$slot][$dia->nombre] = [
                        'instructor'      => $bloque->funcionario->nombre ?? '—',
                        'ambiente'        => $bloque->ambiente
                            ? ($bloque->ambiente->codigo . ' - No.' . $bloque->ambiente->numero)
                            : 'Virtual',
                        'modalidad'       => $bloque->modalidad,
                        'tipoDeFormacion' => $bloque->tipoDeFormacion,
                        'fecha_inicio'    => $asig->fecha_inicio,
                        'fecha_fin'       => $asig->fecha_fin,
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

        if (!$asignacion) {
            return ['ok' => false, 'mensaje' => 'Asignación no encontrada.'];
        }

        $idBloque = $asignacion->idBloque;

        DB::transaction(function () use ($asignacion, $idBloque) {
            $asignacion->delete();

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

    public function listarClasesPorInstructor(int $idFuncionario): array
    {
        $asignaciones = AsignacionModel::with([
                'bloque.dias',
                'bloque.ambiente',
                'bloque.funcionario',
                'ficha.programa',
            ])
            ->whereHas('bloque', fn($q) => $q->where('idFuncionario', $idFuncionario))
            ->get();

        return ['clases' => $asignaciones->values()->all()];
    }
}