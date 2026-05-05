<?php

namespace App\Http\Requests\Competencia;

use Illuminate\Foundation\Http\FormRequest;

class CreateCompetenciaRequest extends FormRequest
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
                'nombreCompetencia' => 'required|string|max:255',
                'codigo' => 'required|string|max:255',
                'tipo' => 'required|string|max:255',
                'idTipoFormacion'=>'required|integer|exists:tipoFormacion,idTipoFormacion',
        ];
    }
}
