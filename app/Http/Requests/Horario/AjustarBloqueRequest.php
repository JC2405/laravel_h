<?php
namespace App\Http\Requests\Horario;
use Illuminate\Foundation\Http\FormRequest;

class AjustarBloqueRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'idBloqueConflicto'              => 'required|integer|exists:bloque_horario,idBloque',
            'nueva_hora_fin_conflicto'       => 'required|date_format:H:i:s',
            'nuevo_bloque'                   => 'required|array',
            'nuevo_bloque.hora_inicio'       => 'required|date_format:H:i:s',
            'nuevo_bloque.hora_fin'          => 'required|date_format:H:i:s',
            'nuevo_bloque.modalidad'         => 'required|in:presencial,virtual',
            'nuevo_bloque.tipoDeFormacion'   => 'required|string|max:255',
            'nuevo_bloque.idFuncionario'     => 'required|integer|exists:funcionario,idFuncionario',
            'nuevo_bloque.idAmbiente'        => 'nullable|integer|exists:ambiente,idAmbiente',
            'nuevo_bloque.dias'              => 'required|array|min:1',
            'nuevo_bloque.dias.*'            => 'integer|exists:dia,idDia',
        ];
    }
}