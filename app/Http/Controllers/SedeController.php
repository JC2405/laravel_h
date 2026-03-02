<?php

namespace App\Http\Controllers;

use App\Http\Requests\Sede\createSedeRequest;
use App\Http\Requests\Sede\UpdateSedeRequest;
use App\Models\SedeModel;
use App\Services\Sede\SedeService;
use Illuminate\Http\Request;

class SedeController extends Controller
{
    public function __construct(protected SedeService $service) { }


    public function index()
    {
      $listarSede =$this->service->getAll();
      return response()->json($listarSede);  
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(createSedeRequest $request)
    {
        $crearSede = $this->service->create($request->validated());
        return response()->json($crearSede);
    }

    /**
     * Display the specified resource.
     */
    public function show($idSede)
    {
        $buscarSede = SedeModel::findOrFail($idSede);
        return response()->json($buscarSede);
    }

    

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSedeRequest $request, $idSede)
    {
        $sedeEdit = SedeModel::findOrFail($idSede);
        $this->service->update($sedeEdit,$request->validated());
        return response()->json($sedeEdit->refresh());       
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($idSede)
    {
        $sedeDelete = SedeModel::findOrFail($idSede); 
        $this->service->destroy($sedeDelete);
        return response()->json(["message" => "Sede Eliminada Correctamente"]);
    }
}
