<?php

namespace App\Http\Controllers;


use App\Http\Requests\Ficha\createFichaRequest;
use App\Http\Requests\Ficha\updateFichaRequest;
use App\Models\FichaModel;
use App\Services\Ficha\FichaService;
use Illuminate\Http\Request;

class FichaController extends Controller
{
    public function __construct(protected FichaService $service) {
    }


    public function index()
    {
        $listarFichas= $this->service->getAll();
        return response()->json($listarFichas);
    }



    public function store(createFichaRequest $request)
    {
        $crearFicha = $this->service->create($request->validated());
        return response()->json($crearFicha);
    }

    public function update(updateFichaRequest $request , $idFicha)
    {
        $editarFicha = FichaModel::findOrFail($idFicha);
        $this->service->update($editarFicha,$request->validated());
        return response()->json($editarFicha);
    }

    public function destroy($idFicha)
    {
        $eliminarFicha = FichaModel::findOrFail($idFicha);
        $this->service->delete($eliminarFicha);
        return response()->json(["message"=>"ficha Eliminada Correctamente"]);
    }

    public function show($codigoFicha)
    {
        $showFicha = $this->service->show($codigoFicha);
        return response()->json($showFicha);
    }

}
