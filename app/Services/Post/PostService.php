<?php

namespace App\Services\Post;

use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PostService
{
    public function getAll(): LengthAwarePaginator
    {
        $query = Post::latest();

        return $query->paginate(Post::PAGINATE);
    }

    public function create(array $data): Post
    {
        return Post::create($data);
    }
}