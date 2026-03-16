<?php

namespace App\Services\Horario;

use Illuminate\Support\Facades\DB;

class DeteccionConflictoService
{
    /**
     * Esquema real (según diagrama):
     *   asignacion  (idAsignacion, idFuncionario, idAmbiente, idFicha, modalidad, estado)
     *   bloque      (idBloque, idAsignacion, fechaInicio, fechaFin, horaInicio, horaFin, estado, observaciones)
     *   bloquedia   (idBloqueDia, idBloque, idDia)
     *   dia         (idDia, nombreDia)
     */

    // ─────────────────────────────────────────────────────────────────────────
    //  INSTRUCTOR
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * ¿El instructor ya tiene clase en ese horario, días y rango de fechas?
     *
     */
    public function detectarConflictoInstructor(int $idFuncionario,string $horaInicio,string $horaFin,array  $dias,string $fechaInicio,string $fechaFin,?int   $excluirBloque = null,?int   $excluirFicha  = null
    ) {
        return DB::table('asignacion as a')
            ->join('bloque as bl',        'bl.idAsignacion',   '=', 'a.idAsignacion')
            ->join('bloqueDia as bd',    'bd.idBloque',       '=', 'bl.idBloque')
            ->join('funcionario as func', 'func.idFuncionario','=', 'a.idFuncionario')
            ->join('ficha as f',          'f.idFicha',         '=', 'a.idFicha')
            ->where('a.idFuncionario', $idFuncionario)
            ->whereIn('bd.idDia', $dias)
            // solapamiento de hora
            ->where('bl.horaInicio', '<', $horaFin)
            ->where('bl.horaFin',    '>', $horaInicio)
            // solapamiento de fecha
            ->where('bl.fechaInicio', '<=', $fechaFin)
            ->where('bl.fechaFin',    '>=', $fechaInicio)
            ->when($excluirBloque, fn($q) => $q->where('bl.idBloque', '!=', $excluirBloque))
            ->when($excluirFicha,  fn($q) => $q->where('a.idFicha',  '!=', $excluirFicha))
            ->select(
                'bl.idBloque',
                'bl.horaInicio',
                'bl.horaFin',
                'bl.fechaInicio',
                'bl.fechaFin',
                'f.codigoFicha',
                DB::raw('func.nombre as instructor_nombre')
            )
            ->first();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  AMBIENTE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * ¿El ambiente ya está ocupado en ese horario, días y rango de fechas?
     *

     */
    public function detectarConflictoAmbiente(int    $idAmbiente,string $horaInicio,string $horaFin,array  $dias,string $fechaInicio,string $fechaFin,?int   $excluirBloque = null,?int   $excluirFicha  = null
    ) {
        return DB::table('asignacion as a')
            ->join('bloque as bl',        'bl.idAsignacion',   '=', 'a.idAsignacion')
            ->join('bloqueDia as bd',    'bd.idBloque',       '=', 'bl.idBloque')
            ->join('funcionario as func', 'func.idFuncionario','=', 'a.idFuncionario')
            ->join('ficha as f',          'f.idFicha',         '=', 'a.idFicha')
            ->where('a.idAmbiente', $idAmbiente)
            ->whereIn('bd.idDia', $dias)
            ->where('bl.horaInicio', '<', $horaFin)
            ->where('bl.horaFin',    '>', $horaInicio)
            ->where('bl.fechaInicio', '<=', $fechaFin)
            ->where('bl.fechaFin',    '>=', $fechaInicio)
            ->when($excluirBloque, fn($q) => $q->where('bl.idBloque', '!=', $excluirBloque))
            ->when($excluirFicha,  fn($q) => $q->where('a.idFicha',  '!=', $excluirFicha))
            ->select(
                'bl.idBloque',
                'bl.horaInicio',
                'bl.horaFin',
                'bl.fechaInicio',
                'bl.fechaFin',
                'f.codigoFicha',
                DB::raw('func.nombre as instructor_nombre')
            )
            ->first();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  HELPER: los dos a la vez
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Devuelve ['instructor' => ..., 'ambiente' => ...].
     * Cada valor es el registro conflictivo o null.
     */
    public function detectarConflictos(int $idFuncionario,int $idAmbiente,string $horaInicio,string $horaFin,array  $dias,string $fechaInicio,string $fechaFin,?int   $excluirBloque = null,?int   $excluirFicha  = null
    ): array {
        return [
            'instructor' => $this->detectarConflictoInstructor(
                $idFuncionario, $horaInicio, $horaFin, $dias,
                $fechaInicio, $fechaFin, $excluirBloque, $excluirFicha
            ),
            'ambiente' => $this->detectarConflictoAmbiente(
                $idAmbiente, $horaInicio, $horaFin, $dias,
                $fechaInicio, $fechaFin, $excluirBloque, $excluirFicha
            ),
        ];
    }
}