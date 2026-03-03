<?php

namespace App\Http\Requests\Ficha;

use Illuminate\Foundation\Http\FormRequest;

class updateFichaRequest extends FormRequest
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
            'fechaInicio'=>'required|date',
            'fechaFin'=>'required|date',
            'estado'=>'required|string|max:255',
            'modalidad'=>'required|string|max:255',
            'idPrograma'=>'required|integer',
            'idAmbiente'=>'required|integer'
        ];
    }
}
