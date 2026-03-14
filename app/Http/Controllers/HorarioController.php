<?php

namespace App\Http\Controllers;

use App\Http\Requests\Horario\CreateAsignacionRequest;
use App\Services\Horario\AsignacionService;

class HorarioController extends Controller
{
    public function __construct(
        protected AsignacionService $asignaciones,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    //  ASIGNACIONES
    // ─────────────────────────────────────────────────────────────────────────

    public function storeAsignacion(CreateAsignacionRequest $request)
    {
        $resultado = $this->asignaciones->crearAsignacion($request->validated());

        if (!$resultado['ok'])
            return response()->json([
                'message'   => $resultado['mensaje'],
                'codigo'    => $resultado['codigo']    ?? null,
                'conflicto' => $resultado['conflicto'] ?? null,
            ], $resultado['codigo'] === 'CONFLICTO' ? 409 : 422);

        return response()->json($resultado['asignacion'], 201);
    }

    public function destroyAsignacion(int $idAsignacion)
    {
        $resultado = $this->asignaciones->eliminarAsignacion($idAsignacion);

        if (!$resultado['ok'])
            return response()->json(['message' => $resultado['mensaje']], 404);

        return response()->json(['message' => $resultado['mensaje']]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  LISTAR
    // ─────────────────────────────────────────────────────────────────────────

    public function horariosPorFicha(int $idFicha)
    {
        $resultado = $this->asignaciones->listarPorFicha($idFicha);

        return response()->json([
            'asignaciones' => $resultado['asignaciones'],
            'grilla'       => $resultado['grilla'],
        ]);
    }

    public function listarFuncionarioPorHorario(int $idFuncionario)
    {
        $resultado = $this->asignaciones->listarClasesPorInstructor($idFuncionario);
        return response()->json($resultado);
    }
}