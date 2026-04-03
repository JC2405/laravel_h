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
        'bloque.dias', 'funcionario', 'ambiente', 'ficha.programa',
    ];

    public function enviarHorarioAprendiz(int $idFicha): array
    {
        return $this->intentar(function () use ($idFicha) {
            $asignaciones = AsignacionModel::with(self::RELACIONES)
                ->where('idFicha', $idFicha)
                ->orderByDesc('idAsignacion')
                ->get();

            $resultado = [
                'ok'           => true,
                'asignaciones' => $asignaciones,
                'grilla'       => $this->grillaService->construirGrillaParaAprendices($asignaciones),
            ];

            $aprendices = AprendizModel::where('idficha', $idFicha)->get();
            $aprendices->each(fn($a) => Mail::to($a->correo)->send(new HorarioAprendizMail($resultado, $a)));

            return ['ok' => true, 'mensaje' => 'Correos enviados correctamente.', 'total' => $aprendices->count()];
        });
    }

    public function enviarHorarioInstructor(int $idFuncionario): array
    {
        return $this->intentar(function () use ($idFuncionario) {
            $funcionario = FuncionarioModel::findOrFail($idFuncionario);
            $horario     = AsignacionModel::with(self::RELACIONES)
                ->where('idFuncionario', $idFuncionario)
                ->get();

            Mail::to($funcionario->correo)->send(new HorarioInstructorMail($horario));

            return ['ok' => true, 'mensaje' => 'Correo enviado correctamente.'];
        });
    }

    private function intentar(callable $accion): array
    {
        try {
            return $accion();
        } catch (\Exception $e) {
            return ['ok' => false, 'mensaje' => $e->getMessage()];
        }
    }
}