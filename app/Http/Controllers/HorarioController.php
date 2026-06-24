<?php

namespace App\Http\Controllers;

use App\Http\Requests\Horario\CreateAsignacionRequest;
use App\Models\BloqueHorarioModel;
use App\Services\Horario\AsignacionService;
use App\Services\Horario\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HorarioController extends Controller
{
    public function __construct(
        protected AsignacionService $asignaciones,
        protected MailService $mail,
    ) {}

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
                [
                    'tipo'       => $res['tipo']       ?? null,
                    'codigoFicha'=> $res['codigoFicha'] ?? null,
                    'conflicto'  => $res['conflicto']  ?? null, // idBloque, horaInicio, horaFin
                ]
            );
        }

        return $this->success($request['asignacion'], 'Asignación creada', 201);
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

    // ================================
    // RESOLUCIÓN DE CONFLICTOS
    // ================================

    /**
     * POST /conflicto/reemplazar
     *
     * Elimina el bloque conflictivo y crea la nueva asignación.
     * El frontend manda el mismo payload de crearAsignacion + idBloque del conflicto.
     *
     * {
     *   "idBloque": 12,           ← viene del campo conflicto.idBloque del 409
     *   "idFuncionario": 5,
     *   "idFicha": 99,
     *   ...resto igual que crearAsignacion
     * }
     */
    public function resolverReemplazando(Request $request)
    {
        $request->validate(['idBloque' => 'required|integer|exists:bloque,idBloque']);
        
        $idBloque= $request->input('idBloque');
        $datosNuevaAsig = $request->except('idBloque');

        $res = $this->asignaciones->resolverReemplazando($idBloque, $datosNuevaAsig);

        if (!$res['ok']) {
            return $this->error($res['mensaje'], $res['http'] ?? 422);
        }

        return $this->success($res['asignacion'], 'Conflicto resuelto: asignación anterior eliminada.', 201);
    }

    /**
     * POST /conflicto/partir
     *
     * Acorta el bloque existente hasta nuevaHoraInicio y crea la nueva asignación.
     * Ej: Gustavo 06:00-12:00 con ficha A → queda 06:00-08:00
     *     Nueva clase de Gustavo 08:00-12:00 con ficha B se crea normalmente.
     *
     * {
     *   "idBloque": 12,
     *   "nuevaHoraInicio": "08:00",   ← punto de corte
     *   "idFuncionario": 5,
     *   "idFicha": 99,
     *   ...resto igual que crearAsignacion
     * }
     */
  public function resolverPartiendo(Request $request)
{
    $request->validate([
        'idBloque'        => 'required|integer|exists:bloque,idBloque',
        'nuevaHoraInicio' => 'required|date_format:H:i:s',
        'nuevaHoraFin'    => 'nullable|date_format:H:i:s',
    ]);

    $idBloque        = $request->input('idBloque');
    $nuevaHoraInicio = $request->input('nuevaHoraInicio');
    $nuevaHoraFin    = $request->input('nuevaHoraFin'); // null si no viene
    $datosNuevaAsig  = $request->except(['idBloque', 'nuevaHoraInicio', 'nuevaHoraFin']);

    $res = $this->asignaciones->resolverPartiendo($idBloque, $nuevaHoraInicio, $datosNuevaAsig, $nuevaHoraFin);

    if (!$res['ok']) {
        return $this->error($res['mensaje'], $res['http'] ?? 422);
    }

    return $this->success([
        'asignacionNueva' => $res['asignacion'],
        'bloqueAcortado'  => $res['bloqueAcortado'],
        'bloqueCola'      => $res['bloqueCola'] ?? null,
    ], 'Conflicto resuelto: bloque partido correctamente.', 201);
}

    // ================================
    // CONSULTAS DE HORARIOS (GRILLAS)
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

    public function horariosPorAmbiente(int $idAmbiente)
    {
        $res = $this->asignaciones->listarClasesPorAmbiente($idAmbiente);
        return $this->success($res);
    }

    // ================================
    // DASHBOARD
    // ================================

    public function dashboardMetrics()
    {
        return $this->success($this->asignaciones->dashboardMetrics());
    }

    // ================================
    // CORREOS
    // ================================

    public function enviarHorarioAprendiz(int $idFicha, Request $request)
    {
        $request->validate([
            'fechaInicio' => 'nullable|date',
            'fechaFin'    => 'nullable|date|after_or_equal:fechaInicio',
        ], [
            'fechaFin.after_or_equal' => 'La fecha fin debe ser igual o posterior a la fecha de inicio.',
        ]);

        $res = $this->mail->enviarHorarioAprendiz(
            $idFicha,
            $request->input('fechaInicio'),
            $request->input('fechaFin')
        );

        if (!$res['ok']) {
            return $this->error($res['mensaje'], 422);
        }

        return $this->success($res, 'Correos enviados');
    }


   public function listarFuncionariosConHorarioPorFecha(string $fechaInicio, string $fechaFin)
    {
        $res = $this->asignaciones->listarFuncionariosPorRangoFechas($fechaInicio, $fechaFin);
        return $this->success($res);
    }



    public function enviarHorario(int $idFuncionario, Request $request)
    {
        $request->validate([
            'fechaInicio' => 'nullable|date',
            'fechaFin'    => 'nullable|date|after_or_equal:fechaInicio',
        ], [
            'fechaFin.after_or_equal' => 'La fecha fin debe ser igual o posterior a la fecha de inicio.',
        ]);

        $res = $this->mail->enviarHorarioInstructor(
            $idFuncionario,
            $request->input('fechaInicio'),
            $request->input('fechaFin')
        );

        if (!$res['ok']) {
            return $this->error($res['mensaje'], 422);
        }

        return $this->success($res, 'Correo enviado');
    }

    public function eliminarPorCodigoFicha($codigoFicha)
    {
        $idFicha = DB::table('ficha')
            ->where('codigoFicha', $codigoFicha)
            ->value('idFicha');

        if (!$idFicha) {
            return $this->error('Ficha no encontrada', 404);
        }

        $this->asignaciones->eliminarAsignacionesYBloques($idFicha);

        return $this->success(null, 'Horario eliminado correctamente');
    }

    // ================================
    // RESPUESTAS ESTÁNDAR
    // ================================

    public function success($data = null, string $message = 'OK', int $code = 200)
    {
        return response()->json([
            'ok'      => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    public function error(string $message = 'Error en el servidor', int $code = 500, $extra = [])
    {
        return response()->json(array_merge([
            'ok'      => false,
            'message' => $message,
        ], $extra), $code);
    }

    public function dashboardCharts(Request $request)
    {
        $anio = (int) $request->query('anio', date('Y'));
        return $this->success($this->asignaciones->dashboardCharts($anio));
    }

    public function enviarHorarioMasivo(Request $request)
    {
    $request->validate([
        'funcionarios_ids'    => 'required|array|min:1',
        'funcionarios_ids.*'  => 'integer|exists:funcionario,idFuncionario',
        'fechaInicio'         => 'nullable|date',
        'fechaFin'            => 'nullable|date|after_or_equal:fechaInicio',
    ]);

    $res = $this->mail->enviarHorarioInstructorMasivo(
        $request->input('funcionarios_ids'),
        $request->input('fechaInicio'),
        $request->input('fechaFin')
    );

    return $this->success($res, 'Proceso de envío masivo finalizado');
    }
}