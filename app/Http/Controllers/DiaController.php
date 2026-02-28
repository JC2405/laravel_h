<?php

namespace App\Http\Controllers;


use App\Http\Requests\Dia\CreateDiaRequest;
use App\Http\Requests\Dia\UpdateDiaRequest;
use App\Models\DiaModel;
use App\Services\Dia\DiaService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DiaController extends Controller
{
    public function __construct(protected DiaService $service) {
    }     

    public function index()
    {
        $listarDias = $this->service->getAll();
        return response()->json($listarDias);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateDiaRequest $request)
    {
        $crearDia = $this->service->create($request->validated());
        return response()->json($crearDia,201);
    }

    /**
     * Display the specified resource.
     */
    public function show($idDia)
    {
        $dia = DiaModel::findOrFail($idDia);
        return response()->json($dia);
    }

   

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDiaRequest $request,$idDia)
    {
        $dia = DiaModel::findOrFail($idDia);
        $this->service->update($dia, $request->validated());
        return response()->json($dia->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($idDia)
    {
        $dia= DiaModel::findOrFail($idDia);
        $this->service->delete($dia);
        return response()->json(['message' => 'Dia Eliminado Correctamente']);
    }
}
