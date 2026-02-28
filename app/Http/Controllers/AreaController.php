<?php

namespace App\Http\Controllers;

use App\Http\Requests\Area\CreateAreaRequest;
use App\Http\Requests\Area\UpdateAreaRequest;
use App\Models\AreaModel;
use App\Services\Area\AreaService;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function __construct(protected AreaService $service){}

    
    public function index()
    {
        $listarArea = $this->service->getAll();
        return response()->json($listarArea);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function store(CreateAreaRequest $request)
    {
        $crearArea = $this->service->create($request->validated());
        return response()->json($crearArea,201);
    }

    public function show($idArea)
    {
        $area = AreaModel::findOrFail($idArea);
        return response()->json($area);
    }

    public function update(UpdateAreaRequest $request, $idArea)
    {
        $area = AreaModel::findOrFail($idArea);
        $this->service->update($area, $request->validated());
        return response()->json($area->fresh());
    }

    
    public function destroy($idArea)
    {
        $area = AreaModel::findOrFail($idArea);
        $this->service->delete($area);
        return response()->json(['message' => 'Área eliminada correctamente']);
    }
}
