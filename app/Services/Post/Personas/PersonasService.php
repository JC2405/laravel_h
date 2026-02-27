<?php

namespace App\Services\Personas;
use App\Models\Personas;
use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;




class PersonasService 
{
    public function getAll(): LengthAwarePaginator 
    {
        $query = Post::latest();

        return $query->paginate(Personas::PAGINATE);
    }


    public function create(array $data): Post
    {
        return Post::create($data);
    }
}