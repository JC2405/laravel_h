<?php

namespace App\Http\Requests\Personas;

use Illuminate\Foundation\Http\FormRequest;

class CreatePersonasRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre'=> 'required|string|maax:255',
            'apellido' => 'required|string',
            'password' => 'required|string',   
        ];
    }
}
