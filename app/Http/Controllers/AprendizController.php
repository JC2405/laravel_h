<?php

namespace App\Http\Controllers;

use App\Http\Requests\Aprendiz\createAprendizRequest;
use App\Http\Requests\Aprendiz\updateAprendizRequest;
use App\Models\AprendizModel;
use App\Services\Aprendiz\AprendizService;
use Illuminate\Http\Request;

class AprendizController extends Controller
{
    public function __construct(protected AprendizService $service) {}



    public function index()
    {
        $listarAprendiz = $this->service->getAll();
        return response()->json($listarAprendiz);
    }


    public function store(createAprendizRequest $request)
    {
        $crearAprendiz = $this->service->store($request->validated());
        return response()->json($crearAprendiz);
    }

    public function update(updateAprendizRequest $request, $idAprendiz)
    {
        $editAprendiz = AprendizModel::findOrFail($idAprendiz);
        $this->service->update($editAprendiz,$request->validated());
        return response()->json($editAprendiz);
    }

    public function destroy($idAprendiz)
    {
        $eliminarAprendiz = AprendizModel::findOrFail($idAprendiz);
        $this->service->delete($eliminarAprendiz);
        return response()->json(["message"=>"Aprendiz eliminado correctamente"]);
    }

    public function show($documento)
    {
        $mostarDocumento = $this->service->show($documento);
        return response()->json($mostarDocumento);
    }
}
