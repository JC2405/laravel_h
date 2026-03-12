<?php

namespace App\Services\Horario;

use Illuminate\Support\Facades\DB;

class DeteccionConflictoService
{
    /**
     * Conflicto de instructor SIN fechas.
     * Usado al crear bloques independientes (sin asignación).
     */
    public function detectarConfictoInstructor(int $idFuncionario,string $horaInicio,string $horaFin,array $dias,?int $excluirBloque = null
    ) {
        return DB::table('bloque_horario as bh')
            ->join('bloque_dia as bd', 'bh.idBloque', '=', 'bd.idBloque')
            ->join('funcionario as f',  'bh.idFuncionario', '=', 'f.idFuncionario')
            ->where('bh.idFuncionario', $idFuncionario)
            ->whereIn('bd.idDia', $dias)
            ->where('bh.hora_inicio', '<', $horaFin)
            ->where('bh.hora_fin',    '>', $horaInicio)
            ->when($excluirBloque, fn($q) => $q->where('bh.idBloque', '!=', $excluirBloque))
            ->select('bh.idBloque', 'bh.hora_inicio', 'bh.hora_fin', DB::raw('f.nombre as instructor_nombre'))
            ->first();
    }

    /**
     * Conflicto de ambiente SIN fechas.
     * Usado al crear bloques independientes (sin asignación).
     */
    public function detectarConflictoAmbiente(int $idAmbiente,string $horaInicio,string $horaFin,array $dias,?int $excluirFicha = null
    ) {
        $query = DB::table('bloque_horario as bh')
            ->join('bloque_dia as bd', 'bh.idBloque', '=', 'bd.idBloque')
            ->join('funcionario as f',  'bh.idFuncionario', '=', 'f.idFuncionario')
            ->where('bh.idAmbiente', $idAmbiente)
            ->whereIn('bd.idDia', $dias)
            ->where('bh.hora_inicio', '<', $horaFin)
            ->where('bh.hora_fin',    '>', $horaInicio);

        if ($excluirFicha) {
            $bloquesDeLaFicha = DB::table('asignacion')
                ->where('idFicha', $excluirFicha)
                ->pluck('idBloque')
                ->toArray();

            if (!empty($bloquesDeLaFicha)) {
                $query->whereNotIn('bh.idBloque', $bloquesDeLaFicha);
            }
        }

        return $query
            ->select('bh.idBloque', 'bh.hora_inicio', 'bh.hora_fin', DB::raw('f.nombre as instructor_nombre'))
            ->first();
    }

    /**
     * Conflicto de instructor CON fechas.
     * Usado al crear asignaciones. Un instructor no puede tener dos fichas
     * en el mismo horario, días y rango de fechas.
     * Sí puede tener fichas distintas en horarios diferentes dentro del mismo período.
     */
    public function detectarConflictoInstructorAsignacion(int $idFuncionario,string $horaInicio,string $horaFin,array $dias,string $fechaInicio,string $fechaFin,?int $excluirBloque = null,?int $excluirFicha  = null
    ) {
        return DB::table('asignacion as a')
        ->join('bloque_horario as bh', 'a.idBloque',      '=', 'bh.idBloque')
        ->join('bloque_dia as bd',     'bh.idBloque',     '=', 'bd.idBloque')
        ->join('funcionario as func',  'bh.idFuncionario','=', 'func.idFuncionario')
        ->join('ficha as f',           'a.idFicha',       '=', 'f.idFicha')
        ->where('bh.idFuncionario', $idFuncionario)
        ->whereIn('bd.idDia', $dias)
        ->where('bh.hora_inicio', '<', $horaFin)
        ->where('bh.hora_fin',    '>', $horaInicio)
        ->where('a.fecha_inicio', '<=', $fechaFin)
        ->where('a.fecha_fin',    '>=', $fechaInicio)
        ->when($excluirBloque, fn($q) => $q->where('bh.idBloque', '!=', $excluirBloque))
        ->when($excluirFicha,  fn($q) => $q->where('a.idFicha',   '!=', $excluirFicha))
            ->select(
                'bh.idBloque',
                'bh.hora_inicio',
                'bh.hora_fin',
                'f.codigoFicha',
                DB::raw('func.nombre as instructor_nombre')
            )
            ->first();
    }

    /**
     * Conflicto de ambiente CON fechas.
     * Usado al crear asignaciones. Un ambiente no puede estar ocupado
     * en el mismo horario, días y rango de fechas.
     */
    public function detectarConflictoAmbienteAsignacion(int $idAmbiente,string $horaInicio,string $horaFin,array $dias,string $fechaInicio,string $fechaFin,?int $excluirBloque = null,?int $excluirFicha  = null
    ) {
        return DB::table('asignacion as a')
            ->join('bloque_horario as bh', 'a.idBloque',      '=', 'bh.idBloque')
            ->join('bloque_dia as bd',     'bh.idBloque',     '=', 'bd.idBloque')
            ->join('funcionario as func',  'bh.idFuncionario','=', 'func.idFuncionario')
            ->join('ficha as f',           'a.idFicha',       '=', 'f.idFicha')
            ->where('bh.idAmbiente', $idAmbiente)
            ->whereIn('bd.idDia', $dias)
            ->where('bh.hora_inicio', '<', $horaFin)
            ->where('bh.hora_fin',    '>', $horaInicio)
            ->where('a.fecha_inicio', '<=', $fechaFin)
            ->where('a.fecha_fin',    '>=', $fechaInicio)
            ->when($excluirBloque, fn($q) => $q->where('bh.idBloque', '!=', $excluirBloque))
            ->when($excluirFicha,  fn($q) => $q->where('a.idFicha',   '!=', $excluirFicha))
            ->select(
                'bh.idBloque',
                'bh.hora_inicio',
                'bh.hora_fin',
                'f.codigoFicha',
                DB::raw('func.nombre as instructor_nombre')
            )
            ->first();
    }
}