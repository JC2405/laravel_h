<?php

namespace App\Http\Controllers;

use App\Http\Requests\TipoContrato\CreateTipoContratoRequest;
use App\Http\Requests\TipoContrato\UpdateTipoContratoRequest;
use App\Models\TipoContratoModel;
use App\Services\TipoContrato\TipoContratoService;
use Illuminate\Http\Request;

class TipoContratoController extends Controller
{

    public function __construct(protected TipoContratoService $service) {
    }
    
    public function index()
    {
        $listarTipoContrato= $this->service->getAll();
        return response()->json($listarTipoContrato);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function store(CreateTipoContratoRequest $request)
    {
        $crearTipoContrato = $this->service->create($request->validated());
        return response()->json($crearTipoContrato,201);
    }


    /**
     * Display the specified resource.
     */
    public function show($idTipoContrato)
    {
        $mostrartipoContrato = TipoContratoModel::findOrFail($idTipoContrato);
        return response()->json($mostrartipoContrato);
    }

    

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTipoContratoRequest $request, $idTipoContrato)
    {
        $tipoContrato = TipoContratoModel::findOrFail($idTipoContrato);
        $this->service->update($tipoContrato, $request -> validated());
        return response()->json($tipoContrato->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($idTipoContrato)
    {
        $tipoContrato = TipoContratoModel::findOrFail($idTipoContrato);
        $this->service->delete($tipoContrato);
        return response()->json(['message' => 'tipo Contrato ELiminado Correctamente']);
    }
}
