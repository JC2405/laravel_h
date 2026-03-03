<?php

namespace App\Http\Requests\Aprendiz;

use Illuminate\Foundation\Http\FormRequest;

class updateAprendizRequest extends FormRequest
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
            'nombre'=>'required|string|max:255',
            'documento'=> 'required|string|max:255',
            'correo'=> 'required|string|max:255',
            'telefono'=> 'required|string|max:255',
            'password'=>'required|string|max:255',
            'estado'=> 'required|string|max:255',
            'idFicha'=> 'required|integer'
        ];
    }
}
