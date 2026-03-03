<?php

namespace App\Http\Requests\Ambiente;

use Illuminate\Foundation\Http\FormRequest;

class createAmbienteRequest extends FormRequest
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
            'codigo'=>'required|string|max:255',
            'capacidad'=>'required|integer',
            'numero'=> 'required|string|max:255',
            'descripcion'=> 'required|string|max:255',
            'bloque'=> 'required|string|max:255',
            'estado'=> 'required|string|max:255',
            'tipoAmbiente'=> 'required|string|max:255',
            'idSede'=>'required|integer',
            'idArea'=>'required|integer'
        ];
    }
}
