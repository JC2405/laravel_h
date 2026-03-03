<?php

namespace App\Http\Controllers;

use App\Http\Requests\Ambiente\createAmbienteRequest;
use App\Http\Requests\Ambiente\updateAmbienteRequest;
use App\Models\AmbienteModel;
use App\Services\Ambiente\AmbienteService;


class AmbienteController extends Controller
{
    public function __construct(protected AmbienteService $service) { }
    
    
    public function index()
    {
        $listarAmbiente = $this->service->getAll();
        return response()->json($listarAmbiente);
    }

    

    /**
     * Store a newly created resource in storage.
     */
    public function store(createAmbienteRequest $request)
    {
        $crearAmbiente = $this->service->create($request->validated());
        return response()->json($crearAmbiente);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        
    }

    

    /**
     * Update the specified resource in storage.
     */
    public function update(updateAmbienteRequest $request,  $idAmbiente)
    {
        $editarAmbiente = AmbienteModel::findOrFail($idAmbiente);
        $this->service->update($editarAmbiente, $request->validated());
        return response()->json($editarAmbiente);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($idAmbiente)
    {
        $eliminarAmbiente = AmbienteModel::findOrFail($idAmbiente);
        $this->service->delete($eliminarAmbiente);
        return response()->json(["message"=>"Ambiente Eliminado Correctamente"]);
    }
}
