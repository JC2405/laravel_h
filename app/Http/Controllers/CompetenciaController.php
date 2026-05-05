<?php

namespace App\Http\Controllers;

use App\Http\Requests\Competencia\CreateCompetenciaRequest;
use App\Http\Requests\Competencia\UpdateCompetenciaRequest;
use App\Models\CompetenciaModel;
use App\Services\Competencia\CompetenciaService;
use Illuminate\Http\Request;

class CompetenciaController extends Controller
{
    public function __construct(protected CompetenciaService $service){}

    
    public function index(Request $request)
    {
        $idTipoFormacion = $request->query('idTipoFormacion') ? (int) $request->query('idTipoFormacion') : null;
        $listarCompetencia = $this->service->getAll($idTipoFormacion);
        return response()->json($listarCompetencia);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function store(CreateCompetenciaRequest $request)
    {
        $crearCompetencia = $this->service->create($request->validated());
        return response()->json($crearCompetencia,201);
    }

    public function show($idCompetencia)
    {
        $competencia = CompetenciaModel::findOrFail($idCompetencia);
        return response()->json($competencia);
    }

    public function update(UpdateCompetenciaRequest $request, $idCompetencia)
    {
        $competencia = CompetenciaModel::findOrFail($idCompetencia);
        $this->service->update($competencia, $request->validated());
        return response()->json($competencia->fresh());
    }

    
    public function destroy($idCompetencia)
    {
        $competencia = CompetenciaModel::findOrFail($idCompetencia);
        $this->service->delete($competencia);
        return response()->json(['message' => 'Competencia eliminada correctamente']);
    }
}
