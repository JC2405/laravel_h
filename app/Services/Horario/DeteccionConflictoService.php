<?php

namespace App\Services\Horario;

use Illuminate\Support\Facades\DB;

/**
 * DeteccionConflictoService
 *
 * Detecta colisiones de horario antes de crear una asignación.
 *
 * Verifica dos tipos de conflicto:
 *  1. INSTRUCTOR: ¿ya tiene otra clase en ese horario?
 *  2. AMBIENTE:   ¿ya está ocupado el aula en ese horario?
 *
 * En ambos casos se comprueba solapamiento en TRES dimensiones:
 *  - Horas del día          (ej. 07:00 – 09:00)
 *  - Días de la semana      (ej. Lunes y Miércoles)
 *  - Rango de fechas        (ej. 2024-03-01 al 2024-06-30)
 *
 * Solo hay conflicto si las tres condiciones se solapan al mismo tiempo.
 */
class DeteccionConflictoService
{
    /**
     * Tablas involucradas (referencia rápida):
     *
     *  asignacion  → idAsignacion, idFuncionario, idAmbiente, idFicha, modalidad, estado
     *  bloque      → idBloque, idAsignacion, fechaInicio, fechaFin, horaInicio, horaFin
     *  bloqueDia   → idBloqueDia, idBloque, idDia
     *  dia         → idDia, nombreDia
     *  funcionario → idFuncionario, nombre, ...
     *  ficha       → idFicha, codigoFicha, ...
     */

    // =========================================================================
    //  CONFLICTO DE INSTRUCTOR
    // =========================================================================

    /**
     * Verifica si el instructor ya tiene otra clase en el mismo horario.
     *
     * Retorna el primer conflicto encontrado o null si está libre.
     *
     * $idInstructor    ID del funcionario
     * $horaInicio      Hora de inicio (ej. "07:00:00")
     * $horaFin         Hora de fin    (ej. "09:00:00")
     * $diasDeLaSemana  IDs de los días (ej. [1, 3] = Lunes y Miércoles)
     * $fechaInicio     Inicio del período (ej. "2024-03-01")
     * $fechaFin        Fin del período    (ej. "2024-06-30")
     * $excluirFicha    ID de ficha a excluir (para no comparar una ficha consigo misma)
     * $excluirBloque   ID de bloque a excluir (útil al editar)
     *  Fila del conflicto (con horaInicio, horaFin, codigoFicha, instructor_nombre) o null
     */
    public function detectarConflictoInstructor(
        int     $idInstructor,
        string  $horaInicio,
        string  $horaFin,
        array   $diasDeLaSemana,
        string  $fechaInicio,
        string  $fechaFin,
        ?int    $excluirBloque = null,
        ?int    $excluirFicha  = null
    ) {
        return DB::table('asignacion as asig')
            // Unir con el bloque para obtener horas y fechas
            ->join('bloque as blq',            'blq.idAsignacion',    '=', 'asig.idAsignacion')
            // Unir con la tabla pivot para filtrar por días
            ->join('bloqueDia as bd',           'bd.idBloque',         '=', 'blq.idBloque')
            // Unir con funcionario para obtener el nombre en la respuesta
            ->join('funcionario as instructor', 'instructor.idFuncionario', '=', 'asig.idFuncionario')
            // Unir con ficha para mostrar el código en el mensaje de conflicto
            ->join('ficha as f',                'f.idFicha',           '=', 'asig.idFicha')

            // Filtrar por el mismo instructor
            ->where('asig.idFuncionario', $idInstructor)

            // Solo los días que coinciden con los días nuevos
            ->whereIn('bd.idDia', $diasDeLaSemana)

            // Solapamiento de horas: inicio_existente < fin_nueva  AND  fin_existente > inicio_nueva
            ->where('blq.horaInicio', '<', $horaFin)
            ->where('blq.horaFin',    '>', $horaInicio)

            // Solapamiento de fechas: inicio_existente <= fin_nueva  AND  fin_existente >= inicio_nueva
            ->where('blq.fechaInicio', '<=', $fechaFin)
            ->where('blq.fechaFin',    '>=', $fechaInicio)

            // Excluir el bloque actual si se está editando (evita conflicto consigo mismo)
            ->when($excluirBloque, fn($q) => $q->where('blq.idBloque',   '!=', $excluirBloque))
            ->when($excluirFicha,  fn($q) => $q->where('asig.idFicha',   '!=', $excluirFicha))

            // Solo los campos necesarios para armar el mensaje de error
            ->select(
                'asig.idFicha',
                'blq.idBloque',
                'blq.horaInicio',
                'blq.horaFin',
                'blq.fechaInicio',
                'blq.fechaFin',
                'f.codigoFicha',
                DB::raw("CONCAT(instructor.nombre,' ',instructor.apellido) as instructor_nombre")
            )
            ->first(); // null si no hay conflicto
    }

    // =========================================================================
    //  CONFLICTO DE AMBIENTE
    // =========================================================================

    /**
     * Verifica si el ambiente (aula) ya está ocupado en el mismo horario.
     *
     * Misma lógica de solapamiento que el conflicto de instructor,
     * pero filtrando por idAmbiente en vez de idFuncionario.
     *
     *  $idAmbiente      ID del ambiente (aula)
     *  $horaInicio      Hora de inicio
     *  $horaFin         Hora de fin
     *  $diasDeLaSemana  IDs de los días
     *  $fechaInicio     Inicio del período
     *  $fechaFin        Fin del período
     *  $excluirBloque   ID de bloque a excluir
     *  $excluirFicha    ID de ficha a excluir
     * 
     */
    public function detectarConflictoAmbiente(
        int     $idAmbiente,
        string  $horaInicio,
        string  $horaFin,
        array   $diasDeLaSemana,
        string  $fechaInicio,
        string  $fechaFin,
        ?int    $excluirBloque = null,
        ?int    $excluirFicha  = null
    ) {
        return DB::table('asignacion as asig')
            ->join('bloque as blq',            'blq.idAsignacion',        '=', 'asig.idAsignacion')
            ->join('bloqueDia as bd',           'bd.idBloque',             '=', 'blq.idBloque')
            ->join('funcionario as instructor', 'instructor.idFuncionario', '=', 'asig.idFuncionario')
            ->join('ficha as f',               'f.idFicha',               '=', 'asig.idFicha')

            // Filtrar por el mismo ambiente
            ->where('asig.idAmbiente', $idAmbiente)

            ->whereIn('bd.idDia', $diasDeLaSemana)

            // Solapamiento de horas
            ->where('blq.horaInicio', '<', $horaFin)
            ->where('blq.horaFin',    '>', $horaInicio)

            // Solapamiento de fechas
            ->where('blq.fechaInicio', '<=', $fechaFin)
            ->where('blq.fechaFin',    '>=', $fechaInicio)

            ->when($excluirBloque, fn($q) => $q->where('blq.idBloque', '!=', $excluirBloque))
            ->when($excluirFicha,  fn($q) => $q->where('asig.idFicha', '!=', $excluirFicha))

            ->select(
                'asig.idficha',
                'blq.idBloque',
                'blq.horaInicio',
                'blq.horaFin',
                'blq.fechaInicio',
                'blq.fechaFin',
                'f.codigoFicha',
                DB::raw('instructor.nombre as instructor_nombre')
            )
            ->first();
    }


    // =========================================================================
//  CONFLICTO DE TITULADA POR FICHA
// =========================================================================

/**
 * Una ficha NO puede tener un bloque de titulada en el mismo período de fechas
 * cuyas horas NO se solapen con el nuevo bloque.
 *
 * Es decir: si ya existe un bloque de titulada en Feb→Abr de 06:00–12:00,
 * solo se permite agregar otro bloque de titulada en ese período si sus horas
 * se tocan o solapan (ej. 06:00–12:00 sí, pero 12:00–18:00 NO).
 */
public function detectarConflictoTitulada(
    int     $idFicha,
    string  $horaInicio,
    string  $horaFin,
    string  $fechaInicio,
    string  $fechaFin,
    ?int    $excluirBloque = null
) {
    return DB::table('bloque as blq')
        ->join('asignacion as asig', 'asig.idAsignacion', '=', 'blq.idAsignacion')
        ->join('ficha as f',         'f.idFicha',         '=', 'asig.idFicha')

        ->where('asig.idFicha', $idFicha)
        ->whereRaw("LOWER(blq.tipoFormacion) = 'titulada'")

        // Fechas SÍ se solapan (mismo período)
        ->where('blq.fechaInicio', '<=', $fechaFin)
        ->where('blq.fechaFin',    '>=', $fechaInicio)

        // Horas NO se solapan ← esto es lo que bloquea
        // No se solapan cuando: fin_existente <= inicio_nuevo  OR  inicio_existente >= fin_nuevo
        ->where(function ($q) use ($horaInicio, $horaFin) {
            $q->where('blq.horaFin',     '<=', $horaInicio)
              ->orWhere('blq.horaInicio', '>=', $horaFin);
        })

        ->when($excluirBloque, fn($q) => $q->where('blq.idBloque', '!=', $excluirBloque))

        ->select(
            'blq.idBloque',
            'blq.horaInicio',
            'blq.horaFin',
            'blq.fechaInicio',
            'blq.fechaFin',
            'f.codigoFicha',
        )
        ->first();
}
}