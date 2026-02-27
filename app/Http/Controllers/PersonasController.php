<?php

namespace App\Http\Controllers;

use App\http\Personas\PersonasService;
use App\Http\Requests\Personas\CreatePersonasRequest;
use App\Services\Personas\PersonasService as PersonasPersonasService;
use Illuminate\Http\Request;





class PersonasController extends Controller
{
        public function __construct(protected PersonasPersonasService $service){}   
        
        
    public function index()
    {
        $posts = $this->service->getAll();
        return response()->json($posts);
    }



    public function store(CreatePersonasRequest $request)
    {
     $post = $this->service->create($request->validated());
     return response()->json($post,201);   
    }
}
