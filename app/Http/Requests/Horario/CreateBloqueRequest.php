<?php
namespace App\Http\Requests\Horario;
use Illuminate\Foundation\Http\FormRequest;

class CreateBloqueRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'hora_inicio'   => 'required|date_format:H:i:s',
            'hora_fin'      => 'required|date_format:H:i:s|after:hora_inicio',
            'modalidad'     => 'required|in:presencial,virtual',
            'idAmbiente'    => 'nullable|integer|exists:ambiente,idAmbiente',
            'idFuncionario' => 'required|integer|exists:funcionario,idFuncionario',
            'dias'          => 'required|array|min:1',
            'dias.*'        => 'integer|exists:dia,idDia',
        ];
    }

    public function messages(): array
    {
        return [
            'hora_fin.after'       => 'La hora fin debe ser posterior a la hora inicio.',
            'modalidad.in'         => 'La modalidad debe ser presencial o virtual.',
            'dias.required'        => 'Debe seleccionar al menos un día.',
            'dias.*.exists'        => 'Uno o más días seleccionados no existen.',
            'idFuncionario.exists' => 'El instructor seleccionado no existe.',
            'idAmbiente.exists'    => 'El ambiente seleccionado no existe.',
        ];
    }
}