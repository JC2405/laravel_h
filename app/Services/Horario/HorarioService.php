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
            ];
        }

        if ($modalidad === 'presencial') {
            $confA = $this->detectarConflictoAmbiente(
                $datos['idAmbiente'],
                $datos['hora_inicio'],
                $datos['hora_fin'],
                $datos['dias'],
                excludeFicha: $datos['idFicha'] ?? null   // ← Ignorar bloques ya asignados a la misma ficha
            );
            if ($confA) {
                return [
                    'ok'         => false,
                    'codigo'     => 'CONFLICTO_AMBIENTE',
                    'mensaje'    => 'El ambiente ya está ocupado de ' .
                                    substr($confA->hora_inicio, 0, 5) . ' a ' .
                                    substr($confA->hora_fin, 0, 5) . ' los días seleccionados.',
                    'conflicto'  => $confA,
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
                'tipoDeFormacion' => $datos['tipoDeFormacion'] ?? null,
            ]);

            $bloque->dias()->attach($datos['dias']);

            return [
                'ok'     => true,
                'bloque' => $bloque->load(['funcionario', 'ambiente', 'dias']),
            ];
        });
    }


    // ══════════════════════════════════════════════════════════
    //  AJUSTAR BLOQUE EXISTENTE + CREAR NUEVO  →  NO SE USA EN EL FRONT, ELIMINADO
    // ══════════════════════════════════════════════════════════


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
            excludeBloque: $bloque->idBloque,
            excludeFicha:  $datos['idFicha']   // ← Ignorar conflictos del mismo instructor en la misma ficha
        );
        if ($confI) {
            return [
                'ok'         => false,
                'codigo'     => 'CONFLICTO_INSTRUCTOR',
                'mensaje'    => 'El instructor ' . $confI->instructor_nombre .
                                ' ya tiene asignación de ' . substr($confI->hora_inicio, 0, 5) .
                                ' a ' . substr($confI->hora_fin, 0, 5) .
                                ' (Ficha ' . $confI->codigoFicha . ') — no puede tener dos fichas distintas en el mismo horario.',
                'conflicto'  => $confI,
            ];
        }

        if ($bloque->idAmbiente) {
            $confA = $this->detectarConflictoAmbienteAsignacion(
                $bloque->idAmbiente, $bloque->hora_inicio, $bloque->hora_fin,
                $idsDias, $datos['fecha_inicio'], $datos['fecha_fin'],
                excludeBloque: $bloque->idBloque,
                excludeFicha:  $datos['idFicha']   // ← Ignorar conflictos de la misma ficha
            );
            if ($confA) {
                return [
                    'ok'         => false,
                    'codigo'     => 'CONFLICTO_AMBIENTE',
                    'mensaje'    => 'El ambiente ya está ocupado de ' .
                                    substr($confA->hora_inicio, 0, 5) . ' a ' .
                                    substr($confA->hora_fin, 0, 5) .
                                    ' (Ficha ' . ($confA->codigoFicha ?? '') . ') — no se puede usar el mismo ambiente para otra ficha en ese horario.',
                    'conflicto'  => $confA,
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
            ->orderByDesc('idAsignacion')  // Más recientes primero → aparecen arriba/encima en el calendario
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
    // Grilla fija de 06:00 a 22:00 en slots de 2 horas
    $slots = [];
    $horaInicio = 6;
    $horaFin    = 24;
    $intervalo  = 2;

    for ($h = $horaInicio; $h < $horaFin; $h += $intervalo) {
        $desde  = sprintf('%02d:00', $h);
        $hasta  = sprintf('%02d:00', $h + $intervalo);
        $slots[] = "$desde - $hasta";
    }

    // Inicializar grilla vacía
    $grilla = [];
    foreach ($slots as $slot) {
        $grilla[$slot] = [];
    }

    foreach ($asignaciones as $asig) {
        $bloque = $asig->bloque;
        if (!$bloque) continue;

        $bloqueInicio = strtotime($bloque->hora_inicio);
        $bloqueFin    = strtotime($bloque->hora_fin);

        foreach ($slots as $slot) {
            [$desde, $hasta] = explode(' - ', $slot);
            $slotInicio = strtotime($desde);
            $slotFin    = strtotime($hasta);

            // El slot está cubierto si el bloque lo solapa (aunque sea parcialmente)
            $solapa = $bloqueInicio < $slotFin && $bloqueFin > $slotInicio;

            if (!$solapa) continue;

            foreach ($bloque->dias as $dia) {
                // Solo sobreescribir si la celda está vacía
                if (!isset($grilla[$slot][$dia->nombre])) {
                    $grilla[$slot][$dia->nombre] = [
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
        }
    }

    // Eliminar filas completamente vacías (Comentado para mostrar todas las franjas de 6am a 12am)
    // $grilla = array_filter($grilla, fn($dias) => !empty($dias));

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
    //  ELIMINAR ASIGNACIÓN Y SU BLOQUE ASOCIADO
    // ══════════════════════════════════════════════════════════
    public function eliminarAsignacionYBloque(int $idAsignacion): array
    {
        $asignacion = AsignacionModel::find($idAsignacion);
        if (!$asignacion) {
            return ['ok' => false, 'mensaje' => 'Asignación no encontrada.'];
        }

        $idBloque = $asignacion->idBloque;

        DB::transaction(function () use ($asignacion, $idBloque) {
            // Eliminar la asignación primero (por la llave foránea)
            $asignacion->delete();

            // Si el bloque ya no tiene otras asignaciones, también lo borramos
            $otrasAsignaciones = AsignacionModel::where('idBloque', $idBloque)->count();
            if ($otrasAsignaciones === 0) {
                $bloque = BloqueHorarioModel::find($idBloque);
                if ($bloque) {
                    $bloque->dias()->detach(); // Pivot table
                    $bloque->delete();         // Bloque actual
                }
            }
        });

        return ['ok' => true, 'mensaje' => 'Asignación y horario eliminados completamente.'];
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
        int $idAmbiente, string $hi, string $hf, array $dias, int $excluir = null, ?int $excludeFicha = null
    ) {
        $query = DB::table('bloque_horario as bh')
            ->join('bloque_dia as bd',  'bh.idBloque',     '=', 'bd.idBloque')
            ->join('funcionario as f',  'bh.idFuncionario','=', 'f.idFuncionario')
            ->where('bh.idAmbiente', $idAmbiente)
            ->whereIn('bd.idDia', $dias)
            ->where('bh.hora_inicio', '<', $hf)
            ->where('bh.hora_fin',    '>', $hi)
            ->when($excluir, fn($q) => $q->where('bh.idBloque', '!=', $excluir));

        // Si se pasa idFicha, excluir bloques que ya estén asignados a esa ficha
        if ($excludeFicha) {
            $bloquesDeLaFicha = DB::table('asignacion')
                ->where('idFicha', $excludeFicha)
                ->pluck('idBloque')
                ->toArray();
            if (!empty($bloquesDeLaFicha)) {
                $query->whereNotIn('bh.idBloque', $bloquesDeLaFicha);
            }
        }

        return $query->select('bh.idBloque', 'bh.hora_inicio', 'bh.hora_fin',
                     DB::raw('f.nombre as instructor_nombre'))
            ->first();
    }

    private function detectarConflictoInstructorAsignacion(
        int $idFuncionario, string $hi, string $hf, array $dias,
        string $fi, string $ff, int $excludeBloque = null, int $excludeFicha = null
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
            ->when($excludeFicha,  fn($q) => $q->where('a.idFicha',   '!=', $excludeFicha))
            ->select('bh.idBloque', 'bh.hora_inicio', 'bh.hora_fin', 'f.codigoFicha',
                     DB::raw('func.nombre as instructor_nombre'))
            ->first();
    }

    private function detectarConflictoAmbienteAsignacion(
        int $idAmbiente, string $hi, string $hf, array $dias,
        string $fi, string $ff, int $excludeBloque = null, int $excludeFicha = null
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
            ->when($excludeFicha,  fn($q) => $q->where('a.idFicha',   '!=', $excludeFicha))
            ->select('bh.idBloque', 'bh.hora_inicio', 'bh.hora_fin', 'f.codigoFicha',
                     DB::raw('func.nombre as instructor_nombre'))
            ->first();
    }
}