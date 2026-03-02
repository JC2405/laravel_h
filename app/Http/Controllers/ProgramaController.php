<?php

namespace App\Http\Controllers;

use App\Http\Requests\Programa\createProgramaRequest;
use App\Http\Requests\Programa\updateProgramaRequest;
use App\Models\ProgramaModel;
use App\Services\Programa\ProgramaService;

class ProgramaController extends Controller
{


    public function __construct(protected ProgramaService $service) { }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $listarProgramas = $this->service->getAll();
        return response()->json($listarProgramas);
    }

  

    /**
     * Store a newly created resource in storage.
     */
    public function store(createProgramaRequest $request)
    {
        $crearPrograma = $this->service->create($request->validated());
        return response()->json($crearPrograma);
    }

    /**
     * Display the specified resource.
     */
    public function show($idPrograma)
    {
        $buscarPrograma = ProgramaModel::findOrFail($idPrograma);
        return response()->json($buscarPrograma);
    }

   

    /**
     * Update the specified resource in storage.
     */
    public function update(updateProgramaRequest $request , $idPrograma)
    {
        $editPrograma = ProgramaModel::findOrFail($idPrograma);
        $this->service->update($editPrograma, $request->validated());
        return response()->json($editPrograma->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($idPrograma)
    {
        $eliminarPrograma = ProgramaModel::findOrFail($idPrograma);
        $this->service->delete($eliminarPrograma);
        return response()->json(["message"=>"Progrma Eliminado Correctamente"]);
    }
}
