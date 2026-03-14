<?php
namespace App\Http\Requests\Horario;
use Illuminate\Foundation\Http\FormRequest;

class CreateBloqueRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'fechaInicio'     => 'required|date_format:H:i:s',
            'fechaFin'        => 'required|date_format:H:i:s|after:hora_inicio',
            'horaInicio'       => 'required|string|max:255',
            'horaFin' => 'required|string|max:255',
            'estado'      => 'nullable|integer|exists:ambiente,idAmbiente',
            'observaciones'   => 'required|integer|exists:funcionario,idFuncionario',
            'idAsignacion'         => 'nullable|integer|exists:asignacion,idAsignacion',
            'dias'            => 'required|array|min:1',
            'dias.*'          => 'integer|exists:dia,idDia',
        ];
    }

    public function messages(): array
    {
        return [
            'hora_fin.after'           => 'La hora fin debe ser posterior a la hora inicio.',
            'modalidad.in'             => 'La modalidad debe ser presencial o virtual.',
            'tipoDeFormacion.required' => 'El tipo de formación es obligatorio.',
            'dias.required'            => 'Debe seleccionar al menos un día.',
            'dias.*.exists'            => 'Uno o más días seleccionados no existen.',
            'idFuncionario.exists'     => 'El instructor seleccionado no existe.',
            'idAmbiente.exists'        => 'El ambiente seleccionado no existe.',
        ];
    }
}