<?php

namespace App\Http\Controllers;

use App\Http\Requests\Area\CreateAreaRequest;
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
    public function create(CreateAreaRequest $request)
    {
        $crearArea = $this->service->create($request->validated());
        return response()->json($crearArea,201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
