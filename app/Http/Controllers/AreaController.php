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


    public function show(AreaModel $areaModel)
    {
        return response()->json($areaModel);
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAreaRequest $request , AreaModel $areaModel)
    {
        $areaModel->update($request->validated());
        return response()->json($areaModel);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AreaModel $areaModel)
    {
        $areaModel->delete();
        return response()->json(['message' => 'Area Eliminada']);
    }
}
