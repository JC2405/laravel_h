<?php

namespace App\Http\Controllers;


use App\Http\Requests\Ficha\createFichaRequest;
use App\Http\Requests\Ficha\updateFichaRequest;
use App\Models\FichaModel;
use App\Services\Ficha\FichaService;
use App\Services\Horario\AsignacionService;
use Illuminate\Http\Request;

class FichaController extends Controller
{
    public function __construct(protected FichaService $service, protected AsignacionService $services) {
    }


    public function index()
    {
        $listarFichas= $this->service->getAll();
        return response()->json($listarFichas);
    }

    public function listarFichasProgramaMunicipio($idMunicipio)
    {
        $data = $this->service->countFichasPorProgramaYMunicipio($idMunicipio);
        return response()->json($data);
    }

    public function store(createFichaRequest $request)
    {
        $crearFicha = $this->service->create($request->validated());
        return response()->json($crearFicha);
    }



    public function update(UpdateFichaRequest $request, $idFicha)
{
    $editarFicha = FichaModel::findOrFail($idFicha);
     $this->service->update($editarFicha, $request->validated());
    //$estadoAnterior = $editarFicha->estado; 
//
   //
//
    //$estadoNuevo = $editarFicha->estado;
//
    //
    //if ($estadoAnterior === 'Activo' && $estadoNuevo === 'Inactivo') {
    //    $res = $this->services->eliminarHorarioPorEstadoFicha($idFicha);
//
    //    if (!$res['ok']) {
    //        return response()->json([
    //            'message' => $res['mensaje']
    //        ], $res['http'] ?? 422);
    //    }
    //}
//
    //return response()->json($editarFicha);
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

    /**
     * GET /api/programas-por-municipio/{idMunicipio}
     * Devuelve los programas distintos que tienen fichas en el municipio indicado.
     */
    public function programasPorMunicipio($idMunicipio)
    {
        return response()->json(
            $this->service->obtenerProgramasPorMunicipio((int) $idMunicipio)
        );
    }

    /**
     * GET /api/fichas-por-programa-municipio/{idPrograma}/{idMunicipio}
     * Devuelve las fichas activas que corresponden al programa y municipio dados.
     */
    public function fichasPorProgramaMunicipio($idPrograma, $idMunicipio)
    {
        return response()->json(
            $this->service->obtenerFichasPorProgramaMunicipio((int) $idPrograma, (int) $idMunicipio)
        );
    }

    public function countActivas()
    {
        return response()->json(['count' => $this->service->countActivas()]);
    }
}
