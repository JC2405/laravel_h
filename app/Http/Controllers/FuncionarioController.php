<?php

namespace App\Http\Controllers;

use App\Http\Requests\Funcionario\createFuncionarioRequest;
use App\Http\Requests\Funcionario\updateFuncionarioRequest;
use App\Models\FuncionarioModel;
use App\Services\Funcionario\FuncionarioService;
use Illuminate\Http\Request;

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

    

    /**
     * Store a newly created resource in storage.
     */
    public function store(createFuncionarioRequest $request)
    {
        $crearFuncionario = $this->service->create($request->validated());
        return response()->json($crearFuncionario);
    }

    /**
     * Display the specified resource.
     */
    public function show($documento)
    {
        $buscarIdFuncionario = FuncionarioModel::where('documento', $documento)->firstOrFail();
        return response()->json($buscarIdFuncionario);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function update(updateFuncionarioRequest $request, $idFuncionario)
    {
        $editarFuncionario = FuncionarioModel::findOrFail($idFuncionario);
        $this->service->update($editarFuncionario, $request ->validated());
        return response()->json($editarFuncionario->fresh());
    }

    /**
     * Update the specified resource in storage.
     */
    public function destroy($idFuncionario)
    {
        $eliminarFuncionario = FuncionarioModel::findOrFail($idFuncionario);
        $this->service->delete($eliminarFuncionario);
        return response()->json(["message"=>"Funcionario Eliminado Correctamente"]);
    }

}
