<?php

namespace App\Http\Controllers;


use App\Http\Requests\Horario\CreateAsignacionRequest;
use App\Http\Requests\Horario\CreateBloqueRequest;
use App\Services\Horario\AsignacionService;
use App\Services\Horario\BloqueService;



class HorarioController extends Controller
{
    public function __construct(protected BloqueService $bloques,protected AsignacionService $asignaciones, ) {}


    public function storeBloque(CreateBloqueRequest $request)
    {
        $resultado = $this->bloques->crearBloque($request->validated());

        if (!$resultado['ok'])
            return response()->json([
                'message'   => $resultado['mensaje'],
                'codigo'    => $resultado['codigo']    ?? null,
                'conflicto' => $resultado['conflicto'] ?? null,
            ], 409);

        return response()->json($resultado['bloque'], 201);
    }

    public function destroyDiaDeBloque(int $idBloque, int $idDia)
    {
        $resultado = $this->bloques->eliminarDiaDeBloque($idBloque, $idDia);

        if (!$resultado['ok'])
            return response()->json([
                'message' => $resultado['mensaje'],
                'codigo'  => $resultado['codigo'] ?? null,
            ], 422);

        return response()->json([
            'message' => $resultado['mensaje'],
            'bloque'  => $resultado['bloque'],
        ]);
    }



    public function storeAsignacion(CreateAsignacionRequest $request)
    {
        $resultado = $this->asignaciones->crearAsignacion($request->validated());

        if (!$resultado['ok'])
            return response()->json([
                'message'   => $resultado['mensaje'],
                'codigo'    => $resultado['codigo']    ?? null,
                'conflicto' => $resultado['conflicto'] ?? null,
            ], 409);

        return response()->json($resultado['asignacion'], 201);
    }

    public function horariosPorFicha(int $idFicha)
    {
        $resultado = $this->asignaciones->listarPorFicha($idFicha);

        return response()->json([
            'asignaciones' => $resultado['asignaciones'],
            'grilla'       => $resultado['grilla'],
        ]);
    }

    public function destroyAsignacion(int $idAsignacion)
    {
        $resultado = $this->asignaciones->eliminarAsignacionYBloque($idAsignacion);

        if (!$resultado['ok'])
            return response()->json(['message' => $resultado['mensaje']], 404);

        return response()->json(['message' => $resultado['mensaje']]);
    }

    public function listarFuncionarioPorHorario(int $idFuncionario)
    {
        $resultado = $this->asignaciones->listarClasesPorInstructor($idFuncionario);
        return response()->json($resultado);
    }
}