<?php

namespace App\Http\Controllers;

use App\Http\Requests\Horario\CreateAsignacionRequest;
use App\Services\Horario\AsignacionService;
use App\Services\Horario\MailService;

class HorarioController extends Controller
{
    public function __construct(
        protected AsignacionService $asignaciones,
        protected MailService $mail,
    ) {}

    // ================================
    // RESPUESTAS ESTÁNDAR
    // ================================

    private function success($data = null, string $message = 'OK', int $code = 200)
    {
        return response()->json([
            'ok'      => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    private function error(string $message = 'Error en el servidor', int $code = 500, $extra = [])
    {
        return response()->json(array_merge([
            'ok'      => false,
            'message' => $message,
        ], $extra), $code);
    }

    // ================================
    // ASIGNACIONES
    // ================================

    public function storeAsignacion(CreateAsignacionRequest $request)
    {
        $res = $this->asignaciones->crearAsignacion($request->validated());

        if (!$res['ok']) {
            return $this->error(
                $res['mensaje'],
                $res['http'] ?? 500,
                ['codigo' => $res['codigo'] ?? null]
            );
        }

        return $this->success($res['asignacion'], 'Asignación creada', 201);
    }

    public function destroyAsignacion(int $id)
    {
        $res = $this->asignaciones->eliminarAsignacion($id);

        if (!$res['ok']) {
            return $this->error($res['mensaje'], $res['http'] ?? 404);
        }

        return $this->success(null, 'Eliminado correctamente');
    }

    public function destroyDiaDeBloque(int $idBloque, int $idDia)
    {
        $res = $this->asignaciones->eliminarDiaDeBloque($idBloque, $idDia);

        if (!$res['ok']) {
            return $this->error(
                $res['mensaje'],
                $res['http'] ?? 422,
                ['codigo' => $res['codigo'] ?? null]
            );
        }

        return $this->success([
            'accion' => $res['accion'] ?? null
        ], $res['mensaje']);
    }

    public function eliminarHorarioPorEstado(int $idFicha)
    {
        $res = $this->asignaciones->eliminarHorarioPorEstadoFicha($idFicha);

        if (!$res['ok']) {
            return $this->error($res['mensaje'], $res['http'] ?? 422);
        }

        return $this->success(null, $res['mensaje']);
    }

    // ================================
    // CONSULTAS
    // ================================

    public function horariosPorFicha(int $idFicha)
    {
        $res = $this->asignaciones->listarAsignacionesPorFicha($idFicha);

        return $this->success($res);
    }

    public function listarFuncionarioPorHorario(int $idFuncionario)
    {
        $res = $this->asignaciones->listarClasesPorInstructor($idFuncionario);

        return $this->success($res);
    }

    public function dashboardMetrics()
    {
        return $this->success(
            $this->asignaciones->dashboardMetrics()
        );
    }

    // ================================
    // CORREOS
    // ================================

    public function enviarHorarioAprendiz(int $idFicha)
    {
        $res = $this->mail->enviarHorarioAprendiz($idFicha);

        if (!$res['ok']) {
            return $this->error($res['mensaje'], 500);
        }

        return $this->success($res, 'Correos enviados');
    }

    public function enviarHorario(int $idFuncionario)
    {
        $res = $this->mail->enviarHorarioInstructor($idFuncionario);

        if (!$res['ok']) {
            return $this->error($res['mensaje'], 500);
        }

        return $this->success($res, 'Correo enviado');
    }
}