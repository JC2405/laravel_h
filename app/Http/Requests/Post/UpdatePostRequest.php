<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ← estaba en false
    }

    public function rules(): array
    {
        return [
            'tittle'  => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'status'  => 'sometimes|required|in:draft,published',
        ];
    }
}