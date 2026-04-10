<?php

namespace App\Http\Controllers;

use App\Http\Requests\Horario\CreateAsignacionRequest;
use App\Services\Horario\AsignacionService;
use App\Services\Horario\MailService;
use Illuminate\Http\Request;

class HorarioController extends Controller
{

    //Services que vamos A utilizar 
    public function __construct(protected AsignacionService $asignaciones,  protected MailService $mail,) {}

    
    // ================================
    // ASIGNACIONES
    // ================================


    //Crear Asignacion depende Del service De asignaciones
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


    // Elimina la asignacion 
    public function destroyAsignacion(int $id)
    {
        $res = $this->asignaciones->eliminarAsignacion($id);

        if (!$res['ok']) {
            return $this->error($res['mensaje'], $res['http'] ?? 404);
        }

        return $this->success(null, 'Eliminado correctamente');
    }


    //Elimina Un Dia de la asignacion Ejm: de lunes a viernes puedo eliminar el jueves con esto 
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
    // CONSULTAS DE HORARIOS (GRILLAS)
    // ================================



    // Este módulo permite listar horarios por:
    // - Ficha, Ambiente,Funcionario (Instructor)

    //  Flujo del proceso:

    // 1. El Service (AsignacionesService) consulta la base de datos y obtiene las asignaciones junto con sus relaciones.
    // 2. Estas asignaciones son enviadas al GrillaService.
    // 3. El GrillaService utiliza un método base llamado `construirGrilla`, el cual se encarga de: Generar la estructura de la grilla (franjas horarias vs días) , Ubicar cada asignación en su respectiva celda según: hora, día y bloque
    // 4. Existen métodos específicos como: construirGrillaParaFicha, construirGrillaParaInstructor,construirGrillaParaAmbiente

    // Lista Horarios Por Fichas  
    public function horariosPorFicha(int $idFicha)
    {
        $res = $this->asignaciones->listarAsignacionesPorFicha($idFicha);

        return $this->success($res);
    }


    // Lista los horarios de funcionarios
    public function listarFuncionarioPorHorario(int $idFuncionario)
    {
        $res = $this->asignaciones->listarClasesPorInstructor($idFuncionario);

        return $this->success($res);
    }


    // Lista los horarios del Ambiente
    public function horariosPorAmbiente(int $idAmbiente){

        $res = $this->asignaciones->listarClasesPorAmbiente($idAmbiente);

        return $this->success($res);
    }





    // ==============================
    // Funcionalidades Del Dahboar
    // ==============================
    public function dashboardMetrics()
    {
        return $this->success(
            $this->asignaciones->dashboardMetrics()
        );
    }




    // ================================
    // CORREOS
    // ================================





    // ================================================================
    // Enviar Horario Aprendiz/ces depende conpletamente del service MailService->enviarHorarioAprendiz
    // Se piden las fechas porque si no se enviarian todas las asignaciones que ha tenido la ficha
    // =================================================================
    public function enviarHorarioAprendiz(int $idFicha, Request $request)
    {
        // Validar fechas solo si se envían
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

   





        // =============================================================       
        // Enviar Horario Funcionario Por Ficha Dependiendo De Las fechas
        // Depende del IdFuncionario y su endPoint es POST /enviarHorario/{id}
        // =============================================================
        public function enviarHorario(int $idFuncionario, Request $request)
    {

        // Valida La Ficha SI la envian
        $request->validate([
            'fechaInicio' => 'nullable|date',
            'fechaFin'    => 'nullable|date|after_or_equal:fechaInicio',
        ], [
            // Pequeña Validacion para que al enviar la hora sirva
            'fechaFin.after_or_equal' => 'La fecha fin debe ser igual o posterior a la fecha de inicio.',
        ]);


        //Llama al service para para Poder Enviar el main (enviarHorarioInstructor viene desde MailService)
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

}