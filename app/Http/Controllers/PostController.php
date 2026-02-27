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

      public function __construct(protected PostService $service) {}

    public function index()
    {
        $posts = $this->service->getAll();
        return response()->json($posts);
    }

    public function store(CreatePostRequest $request)
    {
        $post = $this->service->create($request->validated());
        return response()->json($post, 201);
    }

    public function show(Post $post)
    {
        return response()->json($post);
    }

    public function update(Request $request, Post $post)
    {
        $post->update($request->validated());
        return response()->json($post);
    }

    public function destroy(Post $post)
    {
        $post->delete();
        return response()->json(['message' => 'Post eliminado']);
    }
}
