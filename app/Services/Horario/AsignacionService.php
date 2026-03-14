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
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    //  NORMALIZAR: acepta snake_case o camelCase del frontend
    // ─────────────────────────────────────────────────────────────────────────
    private function normalizar(array $datos): array
    {
        return [
            'idFuncionario' => $datos['idFuncionario'] ?? $datos['id_funcionario']  ?? null,
            'idFicha'       => $datos['idFicha']       ?? $datos['id_ficha']        ?? null,
            'idAmbiente'    => $datos['idAmbiente']    ?? $datos['id_ambiente']     ?? null,
            'modalidad'     => $datos['modalidad']                                  ?? null,
            'estado'        => $datos['estado']                                     ?? 'activo',
            'observaciones' => $datos['observaciones'] ?? $datos['observacion']     ?? null,
            'dias'          => $datos['dias']                                       ?? [],
            'fechaInicio'   => $datos['fechaInicio']   ?? $datos['fecha_inicio']    ?? null,
            'fechaFin'      => $datos['fechaFin']      ?? $datos['fecha_fin']       ?? null,
            'horaInicio'    => $datos['horaInicio']    ?? $datos['hora_inicio']     ?? null,
            'horaFin'       => $datos['horaFin']       ?? $datos['hora_fin']        ?? null,
            'tipoDeFormacion' => $datos['tipoDeFormacion'] ?? $datos['tipo_de_formacion'] ?? null,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  CREAR
    // ─────────────────────────────────────────────────────────────────────────
    public function crearAsignacion(array $datos): array
    {
        $d = $this->normalizar($datos);

        if ($d['fechaInicio'] > $d['fechaFin'])
            return ['ok' => false, 'codigo' => 'FECHA_INVALIDA',
                    'mensaje' => 'La fecha de inicio no puede ser mayor que la fecha fin.'];

        if ($d['horaInicio'] >= $d['horaFin'])
            return ['ok' => false, 'codigo' => 'HORA_INVALIDA',
                    'mensaje' => 'La hora de inicio debe ser menor a la hora fin.'];

        $modalidad = strtolower(trim($d['modalidad'] ?? ''));

        if ($modalidad === 'presencial' && empty($d['idAmbiente']))
            return ['ok' => false, 'codigo' => 'AMBIENTE_REQUERIDO',
                    'mensaje' => 'El ambiente es requerido para la modalidad presencial.'];

        try {
            $asignacion = DB::transaction(function () use ($d, $modalidad) {

                $idAmbiente = $modalidad === 'presencial' ? ($d['idAmbiente'] ?? null) : null;

                // 1. Conflicto de instructor
                $ci = $this->conflictos->detectarConflictoInstructor(
                    $d['idFuncionario'], $d['horaInicio'], $d['horaFin'],
                    $d['dias'], $d['fechaInicio'], $d['fechaFin'],
                    excluirFicha: $d['idFicha'] ?? null
                );
                if ($ci)
                    throw new RuntimeException(
                        'El instructor ' . $ci->instructor_nombre
                        . ' ya tiene clase de ' . substr($ci->horaInicio, 0, 5)
                        . ' a ' . substr($ci->horaFin, 0, 5)
                        . ' (Ficha ' . $ci->codigoFicha . ')'
                        . ' — no puede tener dos fichas en el mismo horario.', 409
                    );

                // 2. Conflicto de ambiente (solo presencial)
                if ($idAmbiente) {
                    $ca = $this->conflictos->detectarConflictoAmbiente(
                        $idAmbiente, $d['horaInicio'], $d['horaFin'],
                        $d['dias'], $d['fechaInicio'], $d['fechaFin'],
                        excluirFicha: $d['idFicha'] ?? null
                    );
                    if ($ca)
                        throw new RuntimeException(
                            'El ambiente ya está ocupado de ' . substr($ca->horaInicio, 0, 5)
                            . ' a ' . substr($ca->horaFin, 0, 5)
                            . ' (Ficha ' . $ca->codigoFicha . ')'
                            . ' — no se puede usar el mismo ambiente para otra ficha en ese horario.', 409
                        );
                }

                // 3. Crear la asignación
                $asignacion = AsignacionModel::create([
                    'idFuncionario' => $d['idFuncionario'],
                    'idFicha'       => $d['idFicha'],
                    'idAmbiente'    => $idAmbiente,
                    'modalidad'     => $modalidad,
                    'estado'        => $d['estado'],
                ]);

                // 4. Crear el bloque
                $bloque = BloqueHorarioModel::create([
                    'idAsignacion'  => $asignacion->idAsignacion,
                    'fechaInicio'   => $d['fechaInicio'],
                    'fechaFin'      => $d['fechaFin'],
                    'horaInicio'    => $d['horaInicio'],
                    'horaFin'       => $d['horaFin'],
                    'estado'        => $d['estado'],
                    'observaciones' => $d['observaciones'],
                ]);

                // 5. Asociar días
                $bloque->dias()->attach($d['dias']);

                return $asignacion->load([
                    'bloque.dias',
                    'funcionario',
                    'ambiente',
                    'ficha.programa',
                ]);
            });

            return ['ok' => true, 'asignacion' => $asignacion];

        } catch (RuntimeException $e) {
            return [
                'ok'      => false,
                'codigo'  => $e->getCode() === 409 ? 'CONFLICTO' : 'ERROR',
                'mensaje' => $e->getMessage(),
            ];
        } catch (\Throwable $e) {
            return [
                'ok'      => false,
                'codigo'  => 'ERROR_INTERNO',
                'mensaje' => 'Ocurrió un error inesperado: ' . $e->getMessage(),
            ];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  ELIMINAR
    // ─────────────────────────────────────────────────────────────────────────
    public function eliminarAsignacion(int $idAsignacion): array
    {
        $asignacion = AsignacionModel::with('bloque')->find($idAsignacion);

        if (!$asignacion)
            return ['ok' => false, 'mensaje' => 'Asignación no encontrada.'];

        DB::transaction(function () use ($asignacion) {
            if ($asignacion->bloque) {
                $asignacion->bloque->dias()->detach();
                $asignacion->bloque->delete();
            }
            $asignacion->delete();
        });

        return ['ok' => true, 'mensaje' => 'Asignación y bloque eliminados correctamente.'];
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  LISTAR
    // ─────────────────────────────────────────────────────────────────────────
    public function listarPorFicha(int $idFicha): array
    {
        $asignaciones = AsignacionModel::with([
                'bloque.dias',
                'funcionario',
                'ambiente',
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

    public function listarClasesPorInstructor(int $idFuncionario): array
    {
        $asignaciones = AsignacionModel::with([
                'bloque.dias',
                'ambiente',
                'funcionario',
                'ficha.programa',
            ])
            ->where('idFuncionario', $idFuncionario)
            ->get();

        return ['clases' => $asignaciones->values()->all()];
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  GRILLA
    // ─────────────────────────────────────────────────────────────────────────
    public function construirGrilla($asignaciones): array
    {
        $slots = [];
        for ($h = 6; $h < 24; $h += 2)
            $slots[] = sprintf('%02d:00', $h) . ' - ' . sprintf('%02d:00', $h + 2);

        $grilla = array_fill_keys($slots, []);

        foreach ($asignaciones as $asig) {
            $bloque = $asig->bloque;
            if (!$bloque) continue;

            // Tolerante: acepta horaInicio o hora_inicio según cómo esté el modelo
            $horaIni = $bloque->horaInicio ?? $bloque->hora_inicio ?? null;
            $horaFin = $bloque->horaFin    ?? $bloque->hora_fin    ?? null;
            if (!$horaIni || !$horaFin) continue;

            $bloqueInicio = strtotime($horaIni);
            $bloqueFin    = strtotime($horaFin);

            // Mismo patrón para fechas
            $fechaIni = $bloque->fechaInicio ?? $bloque->fecha_inicio ?? null;
            $fechaFin = $bloque->fechaFin    ?? $bloque->fecha_fin    ?? null;

            foreach ($slots as $slot) {
                [$desde, $hasta] = explode(' - ', $slot);
                if (!($bloqueInicio < strtotime($hasta) && $bloqueFin > strtotime($desde))) continue;

                foreach ($bloque->dias as $dia) {
                    $nombreDia = $dia->nombreDia ?? $dia->nombre ?? $dia->nombredia ?? null;
                    if (!$nombreDia || isset($grilla[$slot][$nombreDia])) continue;

                    $grilla[$slot][$nombreDia] = [
                        'instructor'   => $asig->funcionario->nombre ?? '—',
                        'ambiente'     => $asig->ambiente
                            ? ($asig->ambiente->codigo . ' - No.' . $asig->ambiente->numero)
                            : 'Virtual',
                        'modalidad'    => $asig->modalidad,
                        'fechaInicio'  => $fechaIni,   // ← siempre camelCase al frontend
                        'fechaFin'     => $fechaFin,
                        'idBloque'     => $bloque->idBloque,
                        'idAsignacion' => $asig->idAsignacion,
                    ];
                }
            }
        }

        return $grilla;
    }
}