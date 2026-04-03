<?php

namespace App\Http\Requests\Horario;

use Illuminate\Foundation\Http\FormRequest;

class CreateAsignacionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // ── Asignación ────────────────────────────────────────────────
            'idFuncionario'  => 'required|integer|exists:funcionario,idFuncionario',
            'idFicha'        => 'required|integer|exists:ficha,idFicha',
            'idAmbiente'     => 'nullable|integer|exists:ambiente,idAmbiente',
            'modalidad'      => 'required|string|in:presencial,virtual',
            'estado'         => 'nullable|string|max:50',

            // ── Bloque ────────────────────────────────────────────────────
            'fechaInicio'    => 'required|date',
            'fechaFin'       => 'required|date|after_or_equal:fechaInicio',
            'horaInicio'     => 'required|date_format:H:i,H:i:s',
            'horaFin'        => 'required|date_format:H:i,H:i:s|after:horaInicio',
            'tipoFormacion'  => 'required|string|max:150',
            'observaciones'  => 'nullable|string|max:255',
            'tipoDeFormacion'=> 'nullable|string|max:255',

            // ── Días ─────────────────────────────────────────────────────
            'dias'           => 'required|array|min:1',
            'dias.*'         => 'integer|exists:dia,idDia',
        ];
    }

    public function messages(): array
    {
        return [
            'fechaFin.after_or_equal' => 'La fecha fin debe ser igual o posterior a la fecha de inicio.',
            'horaFin.after'           => 'La hora fin debe ser posterior a la hora de inicio.',
            'dias.required'           => 'Debes seleccionar al menos un día.',
            'dias.min'                => 'Debes seleccionar al menos un día.',
            'dias.*.exists'           => 'Uno o más días seleccionados no son válidos.',
        ];
    }
}