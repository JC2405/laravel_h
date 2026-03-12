<?php
namespace App\Http\Requests\Horario;
use Illuminate\Foundation\Http\FormRequest;

class CreateAsignacionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // Asignación
            'idBloque'        => 'nullable|integer|exists:bloque_horario,idBloque',
            'idFicha'         => 'required|integer|exists:ficha,idFicha',
            'fecha_inicio'    => 'required|date',
            'fecha_fin'       => 'required|date|after_or_equal:fecha_inicio',
            'estado'          => 'nullable|string|max:20',

            // Bloque (Requeridos si no hay idBloque)
            'hora_inicio'     => 'required_without:idBloque|date_format:H:i:s',
            'hora_fin'        => 'required_without:idBloque|date_format:H:i:s|after:hora_inicio',
            'modalidad'       => 'required_without:idBloque|string|in:presencial,virtual',
            'tipoDeFormacion' => 'required_without:idBloque|string',
            'idFuncionario'   => 'required_without:idBloque|integer|exists:funcionario,idFuncionario',
            'idAmbiente'      => 'nullable|integer|exists:ambiente,idAmbiente',
            'dias'            => 'required_without:idBloque|array',
            'dias.*'          => 'integer|exists:dia,idDia',
        ];
    }
}