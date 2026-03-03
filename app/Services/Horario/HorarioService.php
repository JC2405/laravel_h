<?php
namespace App\Services\Horario;

use App\Models\AsignacionModel;
use App\Models\BloqueHorarioModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class HorarioService
{
    // ══════════════════════════════════════════════════════════
    //  CREAR BLOQUE
    // ══════════════════════════════════════════════════════════
    public function crearBloque(array $datos): array
    {
        if ($datos['hora_inicio'] >= $datos['hora_fin'])
            return ['ok' => false, 'codigo' => 'HORA_INVALIDA',
                    'mensaje' => 'La hora de inicio debe ser menor que la hora de fin.'];

        $modalidad = strtolower(trim($datos['modalidad'] ?? ''));

        if ($modalidad === 'presencial' && empty($datos['idAmbiente']))
            return ['ok' => false, 'codigo' => 'AMBIENTE_REQUERIDO',
                    'mensaje' => 'El ambiente es obligatorio para clases presenciales.'];

        $confI = $this->detectarConflictoInstructor(
            $datos['idFuncionario'],
            $datos['hora_inicio'],
            $datos['hora_fin'],
            $datos['dias']
        );
        if ($confI) {
            return [
                'ok'         => false,
                'codigo'     => 'CONFLICTO_INSTRUCTOR',
                'mensaje'    => 'El instructor ' . $confI->instructor_nombre .
                                ' ya tiene clase de ' . substr($confI->hora_inicio, 0, 5) .
                                ' a ' . substr($confI->hora_fin, 0, 5) . ' los días seleccionados.',
                'conflicto'  => $confI,
                'sugerencia' => $this->sugerirAjuste($confI, $datos['hora_inicio'], $datos['hora_fin']),
            ];
        }

        if ($modalidad === 'presencial') {
            $confA = $this->detectarConflictoAmbiente(
                $datos['idAmbiente'],
                $datos['hora_inicio'],
                $datos['hora_fin'],
                $datos['dias']
            );
            if ($confA) {
                return [
                    'ok'         => false,
                    'codigo'     => 'CONFLICTO_AMBIENTE',
                    'mensaje'    => 'El ambiente ya está ocupado de ' .
                                    substr($confA->hora_inicio, 0, 5) . ' a ' .
                                    substr($confA->hora_fin, 0, 5) . ' los días seleccionados.',
                    'conflicto'  => $confA,
                    'sugerencia' => $this->sugerirAjuste($confA, $datos['hora_inicio'], $datos['hora_fin']),
                ];
            }
        }

        return DB::transaction(function () use ($datos, $modalidad) {
            $bloque = BloqueHorarioModel::create([
                'hora_inicio'   => $datos['hora_inicio'],
                'hora_fin'      => $datos['hora_fin'],
                'modalidad'     => $modalidad,
                'idAmbiente'    => $modalidad === 'presencial' ? ($datos['idAmbiente'] ?? null) : null,
                'idFuncionario' => $datos['idFuncionario'],
            ]);

            $bloque->dias()->attach($datos['dias']);

            return [
                'ok'     => true,
                'bloque' => $bloque->load(['funcionario', 'ambiente', 'dias']),
            ];
        });
    }


    // ══════════════════════════════════════════════════════════
    //  AJUSTAR BLOQUE EXISTENTE + CREAR NUEVO
    //  Ej: Osman 06:00-12:00 → recortar a 06:00-10:00
    //      y crear Inglés 10:00-12:00 en el mismo salón
    // ══════════════════════════════════════════════════════════
    public function ajustarBloqueYCrearNuevo(array $datos): array
    {
        $bloqueConflicto = BloqueHorarioModel::find($datos['idBloqueConflicto']);
        if (!$bloqueConflicto)
            return ['ok' => false, 'mensaje' => 'El bloque en conflicto no existe.'];

        $nuevaHoraFin = $datos['nueva_hora_fin_conflicto'];
        $nuevoInicio  = $datos['nuevo_bloque']['hora_inicio'];

        if ($nuevaHoraFin >= $bloqueConflicto->hora_fin)
            return ['ok' => false,
                    'mensaje' => 'La nueva hora fin debe ser menor a ' .
                                  substr($bloqueConflicto->hora_fin, 0, 5) . '.'];

        if ($nuevaHoraFin <= $bloqueConflicto->hora_inicio)
            return ['ok' => false,
                    'mensaje' => 'La nueva hora fin no puede ser igual o menor a la hora de inicio del bloque.'];

        if ($nuevaHoraFin > $nuevoInicio)
            return ['ok' => false,
                    'mensaje' => 'El ajuste sigue generando solapamiento. ' .
                                  'La hora fin ajustada debe ser ≤ ' . substr($nuevoInicio, 0, 5) . '.'];

        return DB::transaction(function () use ($datos, $bloqueConflicto) {
            $bloqueConflicto->update(['hora_fin' => $datos['nueva_hora_fin_conflicto']]);

            $resultado = $this->crearBloque($datos['nuevo_bloque']);

            if (!$resultado['ok'])
                throw new \Exception($resultado['mensaje']);

            return [
                'ok'             => true,
                'bloqueAjustado' => $bloqueConflicto->fresh()->load(['funcionario', 'ambiente', 'dias']),
                'bloqueNuevo'    => $resultado['bloque'],
            ];
        });
    }


    // ══════════════════════════════════════════════════════════
    //  CREAR ASIGNACIÓN
    // ══════════════════════════════════════════════════════════
    public function crearAsignacion(array $datos): array
    {
        if ($datos['fecha_inicio'] > $datos['fecha_fin'])
            return ['ok' => false, 'mensaje' => 'La fecha de inicio no puede ser mayor que la fecha de fin.'];

        $bloque = BloqueHorarioModel::with(['dias', 'funcionario', 'ambiente'])->find($datos['idBloque']);
        if (!$bloque)
            return ['ok' => false, 'mensaje' => 'El bloque de horario no existe.'];

        $solapeFicha = AsignacionModel::where('idFicha', $datos['idFicha'])
            ->where('idBloque', $datos['idBloque'])
            ->where(fn($q) => $q
                ->where('fecha_inicio', '<=', $datos['fecha_fin'])
                ->where('fecha_fin',    '>=', $datos['fecha_inicio'])
            )->first();

        if ($solapeFicha)
            return ['ok' => false, 'mensaje' => 'Esta ficha ya tiene asignado ese bloque en el período indicado.'];

        $idsDias = $bloque->dias->pluck('idDia')->toArray();

        $confI = $this->detectarConflictoInstructorAsignacion(
            $bloque->idFuncionario, $bloque->hora_inicio, $bloque->hora_fin,
            $idsDias, $datos['fecha_inicio'], $datos['fecha_fin'],
            excludeBloque: $bloque->idBloque
        );
        if ($confI) {
            return [
                'ok'         => false,
                'codigo'     => 'CONFLICTO_INSTRUCTOR',
                'mensaje'    => 'El instructor ' . $confI->instructor_nombre .
                                ' ya tiene asignación de ' . substr($confI->hora_inicio, 0, 5) .
                                ' a ' . substr($confI->hora_fin, 0, 5) .
                                ' (Ficha ' . $confI->codigoFicha . ').',
                'conflicto'  => $confI,
                'sugerencia' => $this->sugerirAjuste($confI, $bloque->hora_inicio, $bloque->hora_fin),
            ];
        }

        if ($bloque->idAmbiente) {
            $confA = $this->detectarConflictoAmbienteAsignacion(
                $bloque->idAmbiente, $bloque->hora_inicio, $bloque->hora_fin,
                $idsDias, $datos['fecha_inicio'], $datos['fecha_fin'],
                excludeBloque: $bloque->idBloque
            );
            if ($confA) {
                return [
                    'ok'         => false,
                    'codigo'     => 'CONFLICTO_AMBIENTE',
                    'mensaje'    => 'El ambiente ya está ocupado de ' .
                                    substr($confA->hora_inicio, 0, 5) . ' a ' .
                                    substr($confA->hora_fin, 0, 5) .
                                    ' (Ficha ' . ($confA->codigoFicha ?? '') . ').',
                    'conflicto'  => $confA,
                    'sugerencia' => $this->sugerirAjuste($confA, $bloque->hora_inicio, $bloque->hora_fin),
                ];
            }
        }

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


    // ══════════════════════════════════════════════════════════
    //  LISTAR HORARIOS POR FICHA + GRILLA VISUAL
    // ══════════════════════════════════════════════════════════
    public function listarPorFicha(int $idFicha): array
    {
        $asignaciones = AsignacionModel::with([
                'bloque.funcionario',
                'bloque.ambiente.sede',
                'bloque.dias',
                'ficha.programa',
            ])
            ->where('idFicha', $idFicha)
            ->orderBy('idAsignacion')
            ->get();

        return [
            'ok'           => true,
            'asignaciones' => $asignaciones,
            'grilla'       => $this->construirGrilla($asignaciones),
        ];
    }


    // ══════════════════════════════════════════════════════════
    //  GRILLA VISUAL  (igual a la imagen: fila=hora, col=día)
    //  dia.nombre  ← columna real de tu migración 000006
    // ══════════════════════════════════════════════════════════
    private function construirGrilla($asignaciones): array
    {
        $grilla = [];

        foreach ($asignaciones as $asig) {
            $bloque = $asig->bloque;
            if (!$bloque) continue;

            $franja = substr($bloque->hora_inicio, 0, 5) . ' - ' . substr($bloque->hora_fin, 0, 5);

            foreach ($bloque->dias as $dia) {
                $grilla[$franja][$dia->nombre] = [     // ✅ dia.nombre
                    'instructor'   => $bloque->funcionario->nombre ?? '—',
                    'ambiente'     => $bloque->ambiente
                                       ? ($bloque->ambiente->codigo . ' - No.' . $bloque->ambiente->numero)
                                       : 'Virtual',
                    'modalidad'    => $bloque->modalidad,
                    'idBloque'     => $bloque->idBloque,
                    'idAsignacion' => $asig->idAsignacion,
                ];
            }
        }

        ksort($grilla);
        return $grilla;
    }


    // ══════════════════════════════════════════════════════════
    //  ELIMINAR BLOQUE
    // ══════════════════════════════════════════════════════════
    public function eliminarBloque(int $idBloque): array
    {
        $bloque = BloqueHorarioModel::find($idBloque);
        if (!$bloque)
            return ['ok' => false, 'mensaje' => 'Bloque no encontrado.'];

        DB::transaction(function () use ($bloque) {
            $bloque->dias()->detach();
            $bloque->delete();
        });

        return ['ok' => true, 'mensaje' => 'Bloque eliminado correctamente.'];
    }


    // ══════════════════════════════════════════════════════════
    //  VERIFICAR DISPONIBILIDAD INSTRUCTOR
    // ══════════════════════════════════════════════════════════
    public function verificarDisponibilidadInstructor(
        int $idFuncionario, string $horaInicio, string $horaFin,
        array $dias, string $fechaInicio, string $fechaFin
    ): array {
        $conflicto = $this->detectarConflictoInstructorAsignacion(
            $idFuncionario, $horaInicio, $horaFin, $dias, $fechaInicio, $fechaFin
        );

        if (!$conflicto)
            return ['disponible' => true];

        return [
            'disponible' => false,
            'conflicto'  => $conflicto,
            'sugerencia' => $this->sugerirAjuste($conflicto, $horaInicio, $horaFin),
        ];
    }


    // ══════════════════════════════════════════════════════════
    //  SUGERIR AJUSTE
    // ══════════════════════════════════════════════════════════
    private function sugerirAjuste(object $conflicto, string $nuevoInicio, string $nuevoFin): array
    {
        $hIni = $conflicto->hora_inicio;
        $hFin = $conflicto->hora_fin;

        if ($nuevoInicio > $hIni && $nuevoInicio < $hFin) {
            return [
                'tipo'                 => 'RECORTAR_FIN',
                'idBloqueAfectado'     => $conflicto->idBloque,
                'instructor_afectado'  => $conflicto->instructor_nombre ?? null,
                'hora_inicio_original' => $hIni,
                'hora_fin_original'    => $hFin,
                'hora_fin_sugerida'    => $nuevoInicio,
                'descripcion'          =>
                    'Se sugiere recortar el bloque de ' . ($conflicto->instructor_nombre ?? 'el instructor') .
                    ' de ' . substr($hIni, 0, 5) . '–' . substr($hFin, 0, 5) .
                    ' a ' . substr($hIni, 0, 5) . '–' . substr($nuevoInicio, 0, 5) .
                    ' para liberar el espacio de ' . substr($nuevoInicio, 0, 5) .
                    ' a ' . substr($nuevoFin, 0, 5) . '.',
            ];
        }

        if ($nuevoFin > $hIni && $nuevoFin < $hFin) {
            return [
                'tipo'                 => 'RECORTAR_INICIO',
                'idBloqueAfectado'     => $conflicto->idBloque,
                'instructor_afectado'  => $conflicto->instructor_nombre ?? null,
                'hora_inicio_original' => $hIni,
                'hora_fin_original'    => $hFin,
                'hora_inicio_sugerida' => $nuevoFin,
                'descripcion'          =>
                    'Se sugiere mover el inicio del bloque de ' . ($conflicto->instructor_nombre ?? 'el instructor') .
                    ' de ' . substr($hIni, 0, 5) . ' a ' . substr($nuevoFin, 0, 5) . '.',
            ];
        }

        return [
            'tipo'        => 'SIN_AJUSTE_POSIBLE',
            'descripcion' => 'El nuevo bloque cubre completamente el bloque en conflicto. Elimínelo o cambie el horario.',
        ];
    }


    // ══════════════════════════════════════════════════════════
    //  PRIVADOS: detección de cruces
    // ══════════════════════════════════════════════════════════
    private function detectarConflictoInstructor(
        int $idFuncionario, string $hi, string $hf, array $dias, int $excluir = null
    ) {
        return DB::table('bloque_horario as bh')
            ->join('bloque_dia as bd',  'bh.idBloque',     '=', 'bd.idBloque')
            ->join('funcionario as f',  'bh.idFuncionario','=', 'f.idFuncionario')
            ->where('bh.idFuncionario', $idFuncionario)
            ->whereIn('bd.idDia', $dias)
            ->where('bh.hora_inicio', '<', $hf)
            ->where('bh.hora_fin',    '>', $hi)
            ->when($excluir, fn($q) => $q->where('bh.idBloque', '!=', $excluir))
            ->select('bh.idBloque', 'bh.hora_inicio', 'bh.hora_fin',
                     DB::raw('f.nombre as instructor_nombre'))
            ->first();
    }

    private function detectarConflictoAmbiente(
        int $idAmbiente, string $hi, string $hf, array $dias, int $excluir = null
    ) {
        return DB::table('bloque_horario as bh')
            ->join('bloque_dia as bd',  'bh.idBloque',     '=', 'bd.idBloque')
            ->join('funcionario as f',  'bh.idFuncionario','=', 'f.idFuncionario')
            ->where('bh.idAmbiente', $idAmbiente)
            ->whereIn('bd.idDia', $dias)
            ->where('bh.hora_inicio', '<', $hf)
            ->where('bh.hora_fin',    '>', $hi)
            ->when($excluir, fn($q) => $q->where('bh.idBloque', '!=', $excluir))
            ->select('bh.idBloque', 'bh.hora_inicio', 'bh.hora_fin',
                     DB::raw('f.nombre as instructor_nombre'))
            ->first();
    }

    private function detectarConflictoInstructorAsignacion(
        int $idFuncionario, string $hi, string $hf, array $dias,
        string $fi, string $ff, int $excludeBloque = null
    ) {
        return DB::table('asignacion as a')
            ->join('bloque_horario as bh', 'a.idBloque',       '=', 'bh.idBloque')
            ->join('bloque_dia as bd',     'bh.idBloque',      '=', 'bd.idBloque')
            ->join('funcionario as func',  'bh.idFuncionario', '=', 'func.idFuncionario')
            ->join('ficha as f',           'a.idFicha',        '=', 'f.idFicha')
            ->where('bh.idFuncionario', $idFuncionario)
            ->whereIn('bd.idDia', $dias)
            ->where('bh.hora_inicio', '<', $hf)
            ->where('bh.hora_fin',    '>', $hi)
            ->where('a.fecha_inicio', '<=', $ff)
            ->where('a.fecha_fin',    '>=', $fi)
            ->when($excludeBloque, fn($q) => $q->where('bh.idBloque', '!=', $excludeBloque))
            ->select('bh.idBloque', 'bh.hora_inicio', 'bh.hora_fin', 'f.codigoFicha',
                     DB::raw('func.nombre as instructor_nombre'))
            ->first();
    }

    private function detectarConflictoAmbienteAsignacion(
        int $idAmbiente, string $hi, string $hf, array $dias,
        string $fi, string $ff, int $excludeBloque = null
    ) {
        return DB::table('asignacion as a')
            ->join('bloque_horario as bh', 'a.idBloque',       '=', 'bh.idBloque')
            ->join('bloque_dia as bd',     'bh.idBloque',      '=', 'bd.idBloque')
            ->join('funcionario as func',  'bh.idFuncionario', '=', 'func.idFuncionario')
            ->join('ficha as f',           'a.idFicha',        '=', 'f.idFicha')
            ->where('bh.idAmbiente', $idAmbiente)
            ->whereIn('bd.idDia', $dias)
            ->where('bh.hora_inicio', '<', $hf)
            ->where('bh.hora_fin',    '>', $hi)
            ->where('a.fecha_inicio', '<=', $ff)
            ->where('a.fecha_fin',    '>=', $fi)
            ->when($excludeBloque, fn($q) => $q->where('bh.idBloque', '!=', $excludeBloque))
            ->select('bh.idBloque', 'bh.hora_inicio', 'bh.hora_fin', 'f.codigoFicha',
                     DB::raw('func.nombre as instructor_nombre'))
            ->first();
    }
}