<?php
namespace App\Http\Requests\Horario;
use Illuminate\Foundation\Http\FormRequest;

class CreateAsignacionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'idBloque'     => 'required|integer|exists:bloque_horario,idBloque',
            'idFicha'      => 'required|integer|exists:ficha,idFicha',
            'fecha_inicio' => 'required|date',
            'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
            'estado'       => 'nullable|string|max:20',
        ];
    }
}