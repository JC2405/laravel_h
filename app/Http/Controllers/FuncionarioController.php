<?php

namespace App\Http\Controllers;

use App\Http\Requests\Funcionario\createFuncionarioRequest;
use App\Http\Requests\Funcionario\updateFuncionarioRequest;
use App\Models\AsignacionModel;
use App\Models\FuncionarioModel;
use App\Services\Funcionario\FuncionarioService;
use App\Services\Horario\AsignacionService;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class FuncionarioController extends Controller
{

    public function __construct(protected FuncionarioService $service) {}
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $listarFuncionarios = $this->service->getAll();
        return response()->json($listarFuncionarios);
    }

    public function crearAdmin(createFuncionarioRequest $request)
    {
        $validated = $request->validated();
        $crearFuncionario = $this->service->crearAdminHorarios($validated, $validated['documento']);
         return response()->json($crearFuncionario, 201);
    }
    

    /**
     * Store a newly created resource in storage.
     */
    public function store(createFuncionarioRequest $request)
    {
        // Obtiene los datos validados del request
        $validated = $request->validated();
        // Crea el funcionario y asigna el documento como contraseña por defecto
        $crearFuncionario = $this->service->create($validated, $validated['documento']);
        return response()->json($crearFuncionario, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($documento)
    {
        $buscarIdFuncionario = $this->service->show($documento);
        return response()->json($buscarIdFuncionario);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function update(updateFuncionarioRequest $request, $idFuncionario)
    {
        $editarFuncionario = FuncionarioModel::findOrFail($idFuncionario);
         $this->service->update($editarFuncionario, $request ->validated());
         return response()->json(['ok' => 'funcionario Editado Corrextamente']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function destroy($idFuncionario)
    {
    try {
        $usuarioAutenticado = JWTAuth::parseToken()->authenticate();
    } catch (\Exception $e) {
        return response()->json(["message" => "Token inválido o expirado."], 401);
    }

    if ($usuarioAutenticado->idFuncionario == $idFuncionario) {
        return response()->json([
            "message" => "No puedes eliminarte a ti mismo."
        ], 403);
    }

    $eliminarFuncionario = FuncionarioModel::findOrFail($idFuncionario);
    
    try {
        $this->service->delete($eliminarFuncionario);
        return response()->json(["message" => "Funcionario Eliminado Correctamente"]);
    } catch (\Illuminate\Database\QueryException $e) {
        if ($e->getCode() == 23000) {
            return response()->json([
                "message" => "No se puede eliminar este instructor porque tiene horarios u otros datos asignados."
            ], 400);
        }
        return response()->json([
            "message" => "Error al eliminar el instructor en la base de datos."
        ], 500);
        }
    }

    public function countInstructores()
    {
        return response()->json(['count' => $this->service->countInstructores()]);
    }

    public function asignarAreaMasivo(Request $request)
    {
        $request->validate([
            'area_id' => 'required|exists:area,idArea',
            'funcionarios_ids' => 'required|array',
            'funcionarios_ids.*' => 'exists:funcionario,idFuncionario'
        ]);

        $areaId = $request->input('area_id');
        $funcionariosIds = $request->input('funcionarios_ids');
        
        $insertedCount = $this->service->asignarAreaMasivo($areaId, $funcionariosIds);

        return response()->json([
            'message' => "Área asignada correctamente a $insertedCount instructores.",
            'inserted_count' => $insertedCount
        ]);
    }
}