<?php
namespace App\Http\Controllers;

use App\Http\Requests\Horario\CreateAsignacionRequest;
use App\Http\Requests\Horario\CreateBloqueRequest;
use App\Models\AsignacionModel;
use App\Services\Horario\HorarioService;

class HorarioController extends Controller
{
    public function __construct(protected HorarioService $service) {}



    public function storeBloque(CreateBloqueRequest $request)
    {
        $resultado = $this->service->crearBloque($request->validated());

        if (!$resultado['ok']) {
            return response()->json([
                'message'   => $resultado['mensaje'],
                'codigo'    => $resultado['codigo']    ?? null,
                'conflicto' => $resultado['conflicto'] ?? null,
            ], 409);
        }

        return response()->json($resultado['bloque'], 201);
    }


    public function storeAsignacion(CreateAsignacionRequest $request)
    {
        $resultado = $this->service->crearAsignacion($request->validated());

        if (!$resultado['ok']) {
            return response()->json([
                'message'   => $resultado['mensaje'],
                'codigo'    => $resultado['codigo']    ?? null,
                'conflicto' => $resultado['conflicto'] ?? null,
            ], 409);
        }

        return response()->json($resultado['asignacion'], 201);
    }

    /** GET /api/horariosPorFicha/{idFicha} */
    public function horariosPorFicha(int $idFicha)
    {
        $resultado = $this->service->listarPorFicha($idFicha);
        return response()->json([
            'asignaciones' => $resultado['asignaciones'],
            'grilla'       => $resultado['grilla'],
        ]);
    }

    /** DELETE /api/eliminarAsignacion/{idAsignacion} */
    public function destroyAsignacion(int $idAsignacion)
    {
        $resultado = $this->service->eliminarAsignacionYBloque($idAsignacion);
        if (!$resultado['ok']) {
            return response()->json(['message' => $resultado['mensaje']], 404);
        }
        return response()->json(['message' => $resultado['mensaje']]);
    }

    
    public function destroyDiaDeBloque(int $idBloque, int $idDia)
    {
    $resultado = $this->service->eliminarDiaDeBloque($idBloque, $idDia);

    if (!$resultado['ok']) {
        return response()->json([
            'message' => $resultado['mensaje'],
            'codigo'  => $resultado['codigo'] ?? null,
        ], 422);
    }

    return response()->json([
        'message' => $resultado['mensaje'],
        'bloque'  => $resultado['bloque'],
    ]);
    }
}