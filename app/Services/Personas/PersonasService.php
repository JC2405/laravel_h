<?php

namespace App\Services\Personas;

use App\Models\Personas;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PersonasService 
{
    public function getAll(): LengthAwarePaginator 
    {
        return Personas::latest()->paginate(Personas::PAGINATE); // ← usaba Post::latest()
    }

    public function create(array $data): Personas  // ← retornaba Post
    {
        return Personas::create($data);  // ← usaba Post::create()
    }
}