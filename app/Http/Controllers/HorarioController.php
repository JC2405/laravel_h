<?php

namespace App\Http\Controllers;

use App\Http\Requests\Horario\CreateAsignacionRequest;
use App\Mail\HorarioAprendizMail;
use App\Services\Horario\AsignacionService;
use App\Mail\HorarioInstructorMail;
use App\Models\AprendizModel;
use App\Models\AsignacionModel;
use App\Models\FuncionarioModel;
use Illuminate\Support\Facades\Mail;


/**
 * HorarioController
 *
 * Recibe las peticiones HTTP relacionadas con horarios y las delega
 * al AsignacionService, que contiene toda la lógica de negocio.
 *
 * El controller solo se encarga de:
 *  - Extraer los datos del request
 *  - Llamar al service
 *  - Devolver la respuesta JSON con el código HTTP correcto
 */
class HorarioController extends Controller
{
    public function __construct(
        protected AsignacionService $servicioAsignaciones,
    ) {}

    // =========================================================================
    //  CREAR / ELIMINAR ASIGNACIONES
    // =========================================================================

    /**
     * POST /api/crearAsignacion
     *
     * Crea una asignación completa: asignación + bloque horario + días.
     * Los datos son validados por CreateAsignacionRequest antes de llegar aquí.
     */
    public function storeAsignacion(CreateAsignacionRequest $request)
    {
        $resultado = $this->servicioAsignaciones->crearAsignacion($request->validated());

        if (!$resultado['ok']) {
            // Elegir el código HTTP apropiado según el tipo de error
            $codigoHTTP = match ($resultado['codigo'] ?? '') {
                'CONFLICTO'                                => 409, // Conflict
                'AMBIENTE_REQUERIDO',
                'FECHA_INVALIDA',
                'HORA_INVALIDA',
                'DIAS_REQUERIDOS'                          => 422, // Unprocessable Entity
                default                                    => $resultado['http'] ?? 422,
            };

            return response()->json([
                'message' => $resultado['mensaje'],
                'codigo'  => $resultado['codigo'] ?? null,
            ], $codigoHTTP);
        }

        // 201 Created al crear exitosamente
        return response()->json($resultado['asignacion'], 201);
    }

    /**
     * DELETE /api/eliminarAsignacion/{idAsignacion}
     *
     * Elimina una asignación completa junto con su bloque horario y días.
     */
    public function destroyAsignacion(int $idAsignacion)
    {
        $resultado = $this->servicioAsignaciones->eliminarAsignacion($idAsignacion);

        if (!$resultado['ok']) {
            $codigoHTTP = $resultado['http'] ?? 404;
            return response()->json(['message' => $resultado['mensaje']], $codigoHTTP);
        }

        return response()->json(['message' => $resultado['mensaje']]);
    }



    /**
     * DELETE /api/eliminarDiaDeBloque/{idBloque}/{idDia}
     *
     * Elimina un día específico de un bloque horario.
     *
     * Comportamiento:
     *  - Si el bloque tiene más de un día → solo elimina ese día.
     *  - Si es el último día → elimina el día, el bloque y la asignación completa.
     *
     * La respuesta incluye el campo "accion" para que el frontend sepa qué pasó:
     *  'DIA_ELIMINADO'       → se quitó solo ese día
     *  'ASIGNACION_ELIMINADA' → se borró todo porque era el último día
     */
    public function destroyDiaDeBloque(int $idBloque, int $idDia)
    {
        $resultado = $this->servicioAsignaciones->eliminarDiaDeBloque($idBloque, $idDia);

        if (!$resultado['ok']) {
            $codigoHTTP = match ($resultado['codigo'] ?? '') {
                'NO_ENCONTRADO'    => 404,
                'DIA_NO_PERTENECE' => 422,
                default            => $resultado['http'] ?? 422,
            };

            return response()->json([
                'message' => $resultado['mensaje'],
                'codigo'  => $resultado['codigo'],
            ], $codigoHTTP);
        }

        return response()->json([
            'message' => $resultado['mensaje'],
            'accion'  => $resultado['accion'], // 'DIA_ELIMINADO' | 'ASIGNACION_ELIMINADA'
        ]);
    }

    // =========================================================================
    //  CONSULTAS
    // =========================================================================

    /**
     * GET /api/horariosPorFicha/{idFicha}
     *
     * Devuelve las asignaciones de una ficha más la grilla lista para
     * renderizar en el calendario del frontend.
     */
    public function horariosPorFicha(int $idFicha)
    {
        $resultado = $this->servicioAsignaciones->listarAsignacionesPorFicha($idFicha);

        return response()->json([
            'asignaciones' => $resultado['asignaciones'],
            'grilla'       => $resultado['grilla'],
        ]);
    }

    /**
     * GET /api/horarioPorInstructor/{idFuncionario}
     *
     * Devuelve las clases de un instructor más su grilla horaria personal.
     */
    public function listarFuncionarioPorHorario(int $idFuncionario)
    {
        $resultado = $this->servicioAsignaciones->listarClasesPorInstructor($idFuncionario);
        return response()->json($resultado);
    }



    /**
     * POST /api/enviarHorarioAprendiz/{idFicha}
     * 
     * Envía el horario a todos los aprendices de una ficha.
     * ✅ NUEVO: Usa construirGrillaParaAprendices() para mostrar TODAS las clases
     * (incluso las superpuestas en la misma franja-día)
     */
    public function enviarHorarioAprendiz(int $idFicha)
    {
        try {
            // Obtener las asignaciones de la ficha con todas las relaciones
            $asignacionesDeLaFicha = AsignacionModel::with([
                    'bloque.dias',
                    'funcionario',
                    'ambiente',
                    'ficha.programa',
                ])
                ->where('idFicha', $idFicha)
                ->orderByDesc('idAsignacion')
                ->get();
     
            // ✅ Construir la grilla que PERMITE múltiples clases por franja-día
            $resultado = [
                'ok'           => true,
                'asignaciones' => $asignacionesDeLaFicha,
                'grilla'       => $this->servicioAsignaciones->construirGrillaParaAprendices($asignacionesDeLaFicha),
            ];
     
            // Obtener los aprendices de la ficha
            $aprendices = AprendizModel::where('idficha', $idFicha)->get();
     
            // Enviar correo a cada aprendiz
            foreach ($aprendices as $aprendiz) {
                Mail::to($aprendiz->correo)
                    ->send(new HorarioAprendizMail($resultado, $aprendiz));
            }
     
            return response()->json([
                'ok' => true,
                'mensaje' => 'Correos enviados correctamente.',
                'aprendices_notificados' => $aprendices->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Error al enviar correos: ' . $e->getMessage()
            ], 500);
        }
    }


    public function enviarHorario(int $idFuncionario)
    {
        try {
            $horario = $this->servicioAsignaciones->listarClasesPorInstructor($idFuncionario);
     
            // Buscar el funcionario para tener su correo
            $funcionario = FuncionarioModel::findOrFail($idFuncionario);
     
            // Enviar el correo — $horario llega a la vista como $horario['grilla']
            Mail::to($funcionario->correo)
                ->send(new HorarioInstructorMail($horario));
     
            return response()->json([
                'ok' => true, 
                'mensaje' => 'Correo enviado correctamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Error al enviar correo del instructor: ' . $e->getMessage()
            ], 500);
        }
    }


    


    public function dashboardMetrics()
    {
        return response()->json($this->servicioAsignaciones->dashboardMetrics());
    }
}