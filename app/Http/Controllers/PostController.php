<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use App\Http\Requests\Post\CreatePostRequest;
use App\Services\Post\PostService;

use PHPUnit\Framework\MockObject\Stub\ReturnReference;

class PostController extends Controller
{

    public function __construct(protected PostService $service ){}
 
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $posts = $this -> service ->getAll();

        return view('posts.index', compact('posts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Return view('post_form',['post' => new Post()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreatePostRequest $request)
    {
      
       $this -> service -> create ($request->validated());

        return Redirect()->route('posts.index') ->with('message', 'Post Creado Exitosamente');
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
