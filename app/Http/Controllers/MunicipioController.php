<?php

namespace App\Http\Controllers;

use App\Http\Requests\Municipio\CreateMunicipioRequest;
use App\Models\MunicipioModel;
use App\Services\Municipio\MunicipioService;
use Illuminate\Http\Request;

class MunicipioController extends Controller
{
    public function __construct(protected MunicipioService $service) {}
    
    public function index()
    {
    $listarMunicipio = $this->service->getAll();
    return response()->json($listarMunicipio);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateMunicipioRequest $request)
    {
        $crearMunicipio = $this->service->create($request->validated());
        return response()->json($crearMunicipio, 201);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($idMunicipio)
    {
        $eliminarMunicipio = MunicipioModel::findOrFail($idMunicipio);
        $this->service->delete($eliminarMunicipio);
        return response()->json(['message' => 'Municipio Eliminado correctamente']);
    }
}
