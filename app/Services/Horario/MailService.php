<?php

namespace App\Services\Horario;

use App\Mail\HorarioAprendizMail;
use App\Mail\HorarioInstructorMail;
use App\Models\AprendizModel;
use App\Models\AsignacionModel;
use App\Models\FuncionarioModel;
use Illuminate\Support\Facades\Mail;

class MailService
{
    public function __construct(
        protected GrillaService $grillaService,
    ) {}

    private const RELACIONES = [
        'bloque.dias', 'funcionario', 'ambiente', 'ficha.programa', 'ficha.sede.municipio',
    ];

    /**
     * Envía el horario a todos los aprendices de una ficha.
     *
     * $idFicha
     * $fechaInicio  Filtro opcional: solo bloques que inician >= esta fecha (Y-m-d)
     * $fechaFin     Filtro opcional: solo bloques que terminan  <= esta fecha (Y-m-d)
     */
    public function enviarHorarioAprendiz(int $idFicha, ?string $fechaInicio = null, ?string $fechaFin = null): array
    {
        return $this->intentar(function () use ($idFicha, $fechaInicio, $fechaFin) {

            $query = AsignacionModel::with(self::RELACIONES)
                ->where('idFicha', $idFicha)
                ->orderByDesc('idAsignacion');

            // Filtrar por rango de fechas del bloque si se proporcionaron
            // Usamos lógica de solapamiento: un bloque se incluye si se cruza
            // con el rango solicitado (fechaInicio <= rangoFin AND fechaFin >= rangoInicio)
            if ($fechaInicio || $fechaFin) {
                $query->whereHas('bloque', function ($q) use ($fechaInicio, $fechaFin) {
                    if ($fechaInicio) {
                        $q->where('fechaFin', '>=', $fechaInicio);
                    }
                    if ($fechaFin) {
                        $q->where('fechaInicio', '<=', $fechaFin);
                    }
                });
            }

            $asignaciones = $query->get();

            if ($asignaciones->isEmpty()) {
                return [
                    'ok'      => false,
                    'mensaje' => 'No hay asignaciones para el rango de fechas indicado.',
                ];
            }

            $resultado = [
                'ok'           => true,
                'asignaciones' => $asignaciones,
                'grilla'       => $this->grillaService->construirGrillaParaAprendices($asignaciones),
            ];

            $aprendices = AprendizModel::where('idFicha', $idFicha)->get();

            if ($aprendices->isEmpty()) {
                return [
                    'ok'      => false,
                    'mensaje' => 'La ficha no tiene aprendices registrados.',
                ];
            }

            $aprendices->each(
                fn($a) => Mail::to($a->correo)->send(new HorarioAprendizMail($resultado, $a))
            );

            return [
                'ok'      => true,
                'mensaje' => 'Correos enviados correctamente.',
                'total'   => $aprendices->count(),
            ];
        });
    }

    /**
     * Envía el horario al correo del instructor.
     *
     * $idFuncionario
     * $fechaInicio   Filtro opcional (Y-m-d)
     * $fechaFin      Filtro opcional (Y-m-d)
     */
    public function enviarHorarioInstructor(int $idFuncionario, ?string $fechaInicio = null, ?string $fechaFin = null): array
    {
        return $this->intentar(function () use ($idFuncionario, $fechaInicio, $fechaFin) {

            $funcionario = FuncionarioModel::findOrFail($idFuncionario);

            $query = AsignacionModel::with(self::RELACIONES)
                ->where('idFuncionario', $idFuncionario);

            // Filtrar por rango de fechas del bloque si se proporcionaron
            // Usamos lógica de solapamiento: un bloque se incluye si se cruza
            // con el rango solicitado (fechaInicio <= rangoFin AND fechaFin >= rangoInicio)
            if ($fechaInicio || $fechaFin) {
                $query->whereHas('bloque', function ($q) use ($fechaInicio, $fechaFin) {
                    if ($fechaInicio) {
                        $q->where('fechaFin', '>=', $fechaInicio);
                    }
                    if ($fechaFin) {
                        $q->where('fechaInicio', '<=', $fechaFin);
                    }
                });
            }

            $horario = $query->get();

            if ($horario->isEmpty()) {
                return [
                    'ok'      => false,
                    'mensaje' => 'No hay asignaciones para el rango de fechas indicado.',
                ];
            }

            // Construir el payload que espera la vista emails/horario.blade.php
            $payload = [
                'clases' => $horario,
                'grilla' => $this->grillaService->construirGrillaParaInstructor($horario),
            ];

            Mail::to($funcionario->correo)->send(new HorarioInstructorMail($payload));

            return ['ok' => true, 'mensaje' => 'Correo enviado correctamente.'];
        });
    }

    // ── AYUDAS ───────────────────────────────────────────────────────────────

    public function intentar(callable $accion): array
    {
        try {
            return $accion();
        } catch (\Exception $e) {
            return ['ok' => false, 'mensaje' => $e->getMessage()];
        }
    }

    public function enviarHorarioInstructorMasivo(array $idsFuncionarios, ?string $fechaInicio = null, ?string $fechaFin = null): array
{
    $enviados = [];
    $fallidos = [];

    foreach ($idsFuncionarios as $idFuncionario) {
        $res = $this->enviarHorarioInstructor($idFuncionario, $fechaInicio, $fechaFin);

        if ($res['ok']) {
            $enviados[] = $idFuncionario;
        } else {
            $fallidos[] = [
                'idFuncionario' => $idFuncionario,
                'motivo'        => $res['mensaje'],
            ];
        }
    }

    return [
        'total_enviados' => count($enviados),
        'total_fallidos' => count($fallidos),
        'enviados'       => $enviados,
        'fallidos'       => $fallidos,
    ];
}
}