<?php

namespace App\Http\Requests\Ficha;

use Illuminate\Foundation\Http\FormRequest;

class createFichaRequest extends FormRequest
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
            'codigoFicha'=>'required|string|max:255',
            'jornada'=>'required|string|max:255',
            'fechaInicio'=>'required|date|max:255',
            'fechaFin'=>'required|date|max:255',
            'estado'=>'required|string|max:255',
            'modalidad'=>'required|string|max:255',
            'idPrograma'=>'required|integer|max:255',
            'idAmbiente'=>'required|integer|max:255'
        ];
    }
}
