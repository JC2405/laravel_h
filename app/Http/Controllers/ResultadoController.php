<?php

namespace App\Http\Controllers;

use App\Http\Requests\Resultado\CreateResultadoRequest;
use App\Http\Requests\Resultado\UpdateResultadoRequest;
use App\Models\ResultadoModel;
use App\Services\Resultado\ResultadoService;
use Illuminate\Http\Request;

class ResultadoController extends Controller
{

    public function __construct(protected ResultadoService $service) {}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $idTipoFormacion = $request->query('idTipoFormacion') ? (int) $request->query('idTipoFormacion') : null;
        $listarResultado = $this->service->getAll($idTipoFormacion);
        return response()->json($listarResultado);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateResultadoRequest $request)
    {
        $createResultado = $this->service->create($request->validated());
        return response()->json($createResultado,201);
    }

    /**
     * Display the specified resource.
     */
    public function show ($idResultado)
    {
        $mostarResultado = ResultadoModel::findOrFail($idResultado);
        return response()->json($mostarResultado);
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateResultadoRequest $request, $idResultado)
    {
        $resultado = ResultadoModel::findOrFail($idResultado);
        $this->service->update($resultado,$request->validated());
        return response()->json($resultado->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($idResultado)
    {
        $resultado = ResultadoModel::findOrFail($idResultado);
        $this->service->delete($resultado);
        return response()->json(['message' => 'Resultado eliminado Correctamente']);
    }
}
