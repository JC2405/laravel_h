<?php

namespace App\Services\Horario;

use Illuminate\Support\Facades\DB;

class DeteccionConflictoService
{

    public function detectarConfictoInstructor(int $idFuncionario,string $horaInicio,string $horaFin,array $dias,?int $excluirBloque = null // al editar, excluye el bloque actual para no conflictuar consigo mismo
    ) {
        return DB::table('bloque_horario as bh')
            ->join('bloque_dia as bd', 'bh.idBloque', '=', 'bd.idBloque')
            ->join('funcionario as f',  'bh.idFuncionario', '=', 'f.idFuncionario')
            ->where('bh.idFuncionario', $idFuncionario)
            ->whereIn('bd.idDia', $dias)                      // alguno de los días coincide
            ->where('bh.hora_inicio', '<', $horaFin)          // solapamiento de horario
            ->where('bh.hora_fin',    '>', $horaInicio)
            ->when($excluirBloque, fn($q) => $q->where('bh.idBloque', '!=', $excluirBloque))
            ->select('bh.idBloque', 'bh.hora_inicio', 'bh.hora_fin', DB::raw('f.nombre as instructor_nombre'))
            ->first();
    }

    public function detectarConflictoAmbiente(int $idAmbiente,string $horaInicio,string $horaFin,array $dias,?int $excluirFicha = null // permite agregar más clases a la misma ficha en el mismo ambiente
    ) {
        $query = DB::table('bloque_horario as bh')
            ->join('bloque_dia as bd', 'bh.idBloque', '=', 'bd.idBloque')
            ->join('funcionario as f',  'bh.idFuncionario', '=', 'f.idFuncionario')
            ->where('bh.idAmbiente', $idAmbiente)
            ->whereIn('bd.idDia', $dias)
            ->where('bh.hora_inicio', '<', $horaFin)
            ->where('bh.hora_fin',    '>', $horaInicio);

        
        if ($excluirFicha) { // Excluir los bloques que ya pertenecen a la ficha actual → no cuenta conflicto consigo misma
            $bloquesDeLaFicha = DB::table('asignacion')
                ->where('idFicha', $excluirFicha)
                ->pluck('idBloque')
                ->toArray();

            if (!empty($bloquesDeLaFicha))
                $query->whereNotIn('bh.idBloque', $bloquesDeLaFicha);
        }
        return $query
            ->select('bh.idBloque', 'bh.hora_inicio', 'bh.hora_fin', DB::raw('f.nombre as instructor_nombre'))
            ->first(); // null = sin conflicto, datos = hay conflicto
    }

    
    public function detectarConflictoInstructorAsignacion(int $idFuncionario,string $horaInicio,string $horaFin,array $dias,string $fechaInicio,string $fechaFin,?int $excluirBloque = null,?int $excluirFicha  = null
    ) {
        return DB::table('asignacion as a')
            ->join('bloque_horario as bh', 'a.idBloque',     '=', 'bh.idBloque')
            ->join('bloque_dia as bd',     'bh.idBloque',    '=', 'bd.idBloque')
            ->join('funcionario as func',  'bh.idFuncionario','=', 'func.idFuncionario')
            ->join('ficha as f',           'a.idFicha',      '=', 'f.idFicha')
            ->where('bh.idFuncionario', $idFuncionario)
            ->whereIn('bd.idDia', $dias)
            ->where('bh.hora_inicio', '<', $horaFin)
            ->where('bh.hora_fin',    '>', $horaInicio)
            ->where('a.fecha_inicio', '<=', $fechaFin)         // solapamiento de fechas
            ->where('a.fecha_fin',    '>=', $fechaInicio)
            ->when($excluirBloque, fn($q) => $q->where('bh.idBloque', '!=', $excluirBloque)) // excluye el bloque actual
            ->when($excluirFicha,  fn($q) => $q->where('a.idFicha',   '!=', $excluirFicha))  // excluye la ficha actual
            ->select('bh.idBloque', 'bh.hora_inicio', 'bh.hora_fin', 'f.codigoFicha', DB::raw('func.nombre as instructor_nombre'))
            ->first();
    }


    public function detectarConflictoAmbienteAsignacion(int $idAmbiente,string $horaInicio,string $horaFin,array $dias,string $fechaInicio,string $fechaFin,?int $excluirBloque = null,?int $excluirFicha  = null
    ) {
        return DB::table('asignacion as a')
            ->join('bloque_horario as bh', 'a.idBloque',     '=', 'bh.idBloque')
            ->join('bloque_dia as bd',     'bh.idBloque',    '=', 'bd.idBloque')
            ->join('funcionario as func',  'bh.idFuncionario','=', 'func.idFuncionario')
            ->join('ficha as f',           'a.idFicha',      '=', 'f.idFicha')
            ->where('bh.idAmbiente', $idAmbiente)
            ->whereIn('bd.idDia', $dias)
            ->where('bh.hora_inicio', '<', $horaFin)
            ->where('bh.hora_fin',    '>', $horaInicio)
            ->where('a.fecha_inicio', '<=', $fechaFin)         
            ->where('a.fecha_fin',    '>=', $fechaInicio)
            ->when($excluirBloque, fn($q) => $q->where('bh.idBloque', '!=', $excluirBloque)) // evita contar el bloque actual
            ->when($excluirFicha,  fn($q) => $q->where('a.idFicha',   '!=', $excluirFicha))  // evita contar la misma ficha
            ->select('bh.idBloque', 'bh.hora_inicio', 'bh.hora_fin', 'f.codigoFicha', DB::raw('func.nombre as instructor_nombre'))
            ->first(); // null = sin conflicto se puede crear, datos = hay conflicto
    }
}