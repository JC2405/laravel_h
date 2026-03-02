<?php

namespace App\Http\Controllers;

use App\Http\Requests\TipoFormacion\createTipoFormacionRequest;
use App\Http\Requests\TipoFormacion\updateTipoFormacionRequest;
use App\Models\TipoFormacionModel;
use App\Services\TipoFormacion\TipoFormacionService;
use Illuminate\Http\Request;

class TipoFormacionController extends Controller
{



    public function __construct(protected TipoFormacionService $service) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $listarTipoFormacion = $this->service->getAll();
        return response()->json($listarTipoFormacion);
    }

    

    /**
     * Store a newly created resource in storage.
     */
    public function store(createTipoFormacionRequest $request)
    {
        $crearTipoFormacion = $this->service->create($request->validated());
        return response()->json($crearTipoFormacion);
    }

    /**
     * Display the specified resource.
     */
    public function show( $idTipoFormcion)
    {
        $mostrarTipoFormacion = TipoFormacionModel::finOrFail($idTipoFormcion);
        return response()->json($mostrarTipoFormacion);
    }

 

    /**
     * Update the specified resource in storage.
     */
    public function update(updateTipoFormacionRequest $request, $idTipoFormacion)
    {
        $editarTipoFormacion = TipoFormacionModel::findOrFail($idTipoFormacion);
        $this->service->update($editarTipoFormacion,$request->validated());
        return response()->json($editarTipoFormacion);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($idTipoFormacion)
    {
        $eliminarTipoFormacion = TipoFormacionModel::findOrFail($idTipoFormacion);
        $this->service->delete($eliminarTipoFormacion);
        return response()->json(["message"=>"Tipo Formacion Eliminada Corretamente"]);
    }
}
