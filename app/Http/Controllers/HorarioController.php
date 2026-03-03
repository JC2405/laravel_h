<?php
namespace App\Http\Controllers;

use App\Http\Requests\Horario\AjustarBloqueRequest;
use App\Http\Requests\Horario\CreateAsignacionRequest;
use App\Http\Requests\Horario\CreateBloqueRequest;
use App\Models\AsignacionModel;
use App\Models\BloqueHorarioModel;
use App\Services\Horario\HorarioService;
use Illuminate\Http\Request;

class HorarioController extends Controller
{
    public function __construct(protected HorarioService $service) {}

    /** GET /api/listarBloques */
    public function indexBloques()
    {
        $bloques = BloqueHorarioModel::with(['funcionario', 'ambiente.sede', 'dias'])
            ->orderBy('hora_inicio')
            ->paginate(BloqueHorarioModel::PAGINATION);
        return response()->json($bloques);
    }

    /** POST /api/crearBloque */
    public function storeBloque(CreateBloqueRequest $request)
    {
        $resultado = $this->service->crearBloque($request->validated());

        if (!$resultado['ok']) {
            return response()->json([
                'message'    => $resultado['mensaje'],
                'codigo'     => $resultado['codigo']     ?? null,
                'conflicto'  => $resultado['conflicto']  ?? null,
                'sugerencia' => $resultado['sugerencia'] ?? null,
            ], 409);
        }

        return response()->json($resultado['bloque'], 201);
    }

    /**
     * POST /api/ajustarBloqueYCrear
     *
     * Body ejemplo:
     * {
     *   "idBloqueConflicto": 5,
     *   "nueva_hora_fin_conflicto": "10:00:00",
     *   "nuevo_bloque": {
     *     "hora_inicio": "10:00:00",
     *     "hora_fin": "12:00:00",
     *     "modalidad": "presencial",
     *     "idFuncionario": 12,
     *     "idAmbiente": 3,
     *     "dias": [1, 2, 3, 4, 5]
     *   }
     * }
     */
    public function ajustarBloqueYCrear(AjustarBloqueRequest $request)
    {
        $resultado = $this->service->ajustarBloqueYCrearNuevo($request->validated());

        if (!$resultado['ok'])
            return response()->json(['message' => $resultado['mensaje']], 409);

        return response()->json([
            'message'        => 'Bloque ajustado y nuevo bloque creado correctamente.',
            'bloqueAjustado' => $resultado['bloqueAjustado'],
            'bloqueNuevo'    => $resultado['bloqueNuevo'],
        ], 201);
    }

    /** DELETE /api/eliminarBloque/{idBloque} */
    public function destroyBloque(int $idBloque)
    {
        $resultado = $this->service->eliminarBloque($idBloque);
        return response()->json(['message' => $resultado['mensaje']], $resultado['ok'] ? 200 : 404);
    }

    /** GET /api/listarAsignaciones */
    public function indexAsignaciones()
    {
        $asignaciones = AsignacionModel::with([
                'bloque.funcionario', 'bloque.ambiente.sede',
                'bloque.dias', 'ficha.programa',
            ])
            ->orderBy('idAsignacion')
            ->paginate(AsignacionModel::PAGINATION);
        return response()->json($asignaciones);
    }

    /** POST /api/crearAsignacion */
    public function storeAsignacion(CreateAsignacionRequest $request)
    {
        $resultado = $this->service->crearAsignacion($request->validated());

        if (!$resultado['ok']) {
            return response()->json([
                'message'    => $resultado['mensaje'],
                'codigo'     => $resultado['codigo']     ?? null,
                'conflicto'  => $resultado['conflicto']  ?? null,
                'sugerencia' => $resultado['sugerencia'] ?? null,
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
        $asignacion = AsignacionModel::findOrFail($idAsignacion);
        $asignacion->delete();
        return response()->json(['message' => 'Asignación eliminada correctamente.']);
    }

    /** POST /api/verificarDisponibilidad */
    public function verificarDisponibilidad(Request $request)
    {
        $request->validate([
            'idFuncionario' => 'required|integer',
            'hora_inicio'   => 'required|date_format:H:i:s',
            'hora_fin'      => 'required|date_format:H:i:s',
            'dias'          => 'required|array|min:1',
            'dias.*'        => 'integer|exists:dia,idDia',
            'fecha_inicio'  => 'required|date',
            'fecha_fin'     => 'required|date',
        ]);

        return response()->json(
            $this->service->verificarDisponibilidadInstructor(
                $request->idFuncionario,
                $request->hora_inicio,
                $request->hora_fin,
                $request->dias,
                $request->fecha_inicio,
                $request->fecha_fin
            )
        );
    }
}