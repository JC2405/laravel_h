<?php

namespace App\Services\Horario;

use App\Models\AsignacionModel;
use App\Models\BloqueHorarioModel;
use App\Services\Ficha\FichaService;
use App\Services\Funcionario\FuncionarioService;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class AsignacionService
{
    public function __construct(
        protected DeteccionConflictoService $conflictos,
        protected FichaService $fichaService,
        protected GrillaService $grillaService,
        protected FuncionarioService $funcionarioService,
    ) {}

    // ================================
    // CREAR
    // ================================
    public function crearAsignacion(array $datosEntrada): array
    {
        $datos = $this->normalizarDatosEntrada($datosEntrada);

        $error = $this->validar($datos);
        if ($error) return $error;
    
        try {
            $asignacion = DB::transaction(function () use ($datos) {

                $this->verificarConflictos($datos);

                $asignacion = AsignacionModel::create([
                    'idFuncionario' => $datos['idInstructor'],
                    'idFicha'       => $datos['idFicha'],
                    'idAmbiente'    => $this->resolverAmbiente($datos),
                    'modalidad'     => strtolower($datos['modalidad']),
                    'estado'        => $datos['estado'],
                ]);

                $this->crearBloque($asignacion->idAsignacion, $datos);

                return $asignacion->load([
                    'bloque.dias',
                    'funcionario',
                    'ambiente',
                    'ficha.programa',
                ]);
            });

            return ['ok' => true, 'asignacion' => $asignacion];

        } catch (RuntimeException $e) {
            return $this->respuestaError('CONFLICTO', $e->getMessage(), 409);
        } catch (Throwable $e) {
            return $this->respuestaError('ERROR', $e->getMessage(), 500);
        }
    }

    // ================================
    // ELIMINAR ASIGNACIÓN
    // ================================
    public function eliminarAsignacion(int $id): array
    {
        $asignacion = AsignacionModel::with('bloque.dias')->find($id);

        if (!$asignacion) {
            return $this->respuestaError('NO_ENCONTRADO', 'No existe', 404);
        }

        DB::transaction(function () use ($asignacion) {
            if ($asignacion->bloque) {
                $this->eliminarBloqueCompleto($asignacion->bloque);
            }
            $asignacion->delete();
        });

        return ['ok' => true];
    }

    // ================================
    // ELIMINAR POR ESTADO DE FICHA
    // ================================
    public function eliminarHorarioPorEstadoFicha(int $idFicha): array
    {
        $verificacion = $this->fichaService->verificarEstadoFicha($idFicha);

        if (!$verificacion['ok']) return $verificacion;

        DB::transaction(fn() => $this->eliminarAsignacionesYBloques($idFicha));

        return ['ok' => true, 'mensaje' => 'Horario eliminado correctamente'];
    }

    private function eliminarAsignacionesYBloques(int $idFicha): void
    {
        AsignacionModel::with('bloque.dias')
            ->where('idFicha', $idFicha)
            ->get()
            ->each(function ($asignacion) {
                if ($asignacion->bloque) {
                    $this->eliminarBloqueCompleto($asignacion->bloque);
                }
                $asignacion->delete();
            });
    }


    public function eliminarHorarioPorEstadoFuncionario(int $idFuncionario):array
    {
        $verificacion = $this->funcionarioService->verificarEstadoPorInstructor($idFuncionario);

        if (!$verificacion['ok']) return $verificacion;
        DB::transaction(fn()=> $this->eliminarAsignacionesYBloques($idFuncionario));
        return ['ok' => true, 'mensaje' => 'horario Eliminado Correctamente'];
    } 
    // ================================
    // ELIMINAR DÍA DE BLOQUE
    // ================================
    public function eliminarDiaDeBloque(int $idBloque, int $idDia): array
    {
        $bloque = BloqueHorarioModel::with('dias')->find($idBloque);

        if (!$bloque) {
            return $this->respuestaError('NO_ENCONTRADO', 'Bloque horario no encontrado.', 404);
        }

        $idsActuales = $bloque->dias->pluck('idDia')->toArray();

        if (!in_array($idDia, $idsActuales)) {
            return $this->respuestaError('DIA_NO_PERTENECE', 'El día no pertenece a este bloque.', 422);
        }

        if (count($idsActuales) === 1) {
            DB::transaction(function () use ($bloque) {
                $idAsignacion = $bloque->idAsignacion;
                $this->eliminarBloqueCompleto($bloque);
                AsignacionModel::where('idAsignacion', $idAsignacion)->delete();
            });

            return [
                'ok'      => true,
                'accion'  => 'ASIGNACION_ELIMINADA',
                'mensaje' => 'Era el único día del bloque. Se eliminó la asignación completa.',
            ];
        }

        DB::transaction(fn() => $bloque->dias()->detach($idDia));

        return [
            'ok'      => true,
            'accion'  => 'DIA_ELIMINADO',
            'mensaje' => 'Día eliminado correctamente del bloque horario.',
        ];
    }

    // ================================
    // CONSULTAS
    // ================================
    public function listarAsignacionesPorFicha(int $idFicha): array
    {
        $asignaciones = AsignacionModel::with(['bloque.dias', 'funcionario', 'ambiente', 'ficha.programa'])
            ->where('idFicha', $idFicha)
            ->orderByDesc('idAsignacion')
            ->get();

        return [
            'ok'           => true,
            'asignaciones' => $asignaciones,
            'grilla'       => $this->grillaService->construirGrillaParaFicha($asignaciones),
        ];
    }

    public function listarClasesPorInstructor(int $idInstructor): array
    {
        $asignaciones = AsignacionModel::with(['bloque.dias', 'funcionario', 'ambiente', 'ficha.programa'])
            ->where('idFuncionario', $idInstructor)
            ->get();

        return [
            'clases' => $asignaciones->values()->all(),
            'grilla' => $this->grillaService->construirGrillaParaInstructor($asignaciones),
        ];
    }


    public function listarClasesPorAmbiente(int $idAmbiente): array
    {
        $asignaciones = AsignacionModel::with(['bloque.dias', 'funcionario' , 'ambiente', 'ficha.programa'])
           ->where('idAmbiente', $idAmbiente)
           ->get();
           
           return [
                'ok' => true,
                'asignaciones' => $asignaciones,
                'grilla' => $this->grillaService->construirGrillaParaAmbiente($asignaciones),
           ];
    }
    // ================================
    // DASHBOARD
    // ================================
    public function dashboardMetrics(): array
    {
        $diasMapa  = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 0 => 'Domingo'];
        $diaActual = $diasMapa[date('w')];

        $clasesDelDia = AsignacionModel::whereHas('bloque.dias', fn($q) =>
            $q->where('nombreDia', $diaActual)->orWhere('nombre', $diaActual)
        )->count();

        $horariosActivos = AsignacionModel::count();

        return [
            'clases_del_dia'   => $clasesDelDia,
            'horarios_activos' => $horariosActivos,
            'alertas'          => $horariosActivos > 0
                ? ["El sistema tiene {$horariosActivos} horarios activos en total."]
                : ["No hay horarios activos actualmente."],
        ];
    }

    // ================================
    // BLOQUE
    // ================================
    private function crearBloque(int $idAsignacion, array $datos): void
    {
        $bloque = BloqueHorarioModel::create([
            'idAsignacion'  => $idAsignacion,
            'fechaInicio'   => $datos['fechaInicioPeriodo'],
            'fechaFin'      => $datos['fechaFinPeriodo'],
            'horaInicio'    => $datos['horaInicioClase'],
            'horaFin'       => $datos['horaFinClase'],
            'tipoFormacion' => $datos['tipoFormacion'],
            'estado'        => $datos['estado'],
            'observaciones' => $datos['observaciones'],
        ]);

        $bloque->dias()->attach($datos['diasDeLaSemana']);
    }

    private function eliminarBloqueCompleto($bloque): void
    {
        $bloque->dias()->detach();
        $bloque->delete();
    }

    // ================================
    // VALIDACIONES
    // ================================
    private function validar(array $datos): ?array
    {
        if ($datos['fechaInicioPeriodo'] > $datos['fechaFinPeriodo']) {
            return $this->respuestaError('FECHA_INVALIDA', 'Fecha inválida');
        }

        if ($datos['horaInicioClase'] >= $datos['horaFinClase']) {
            return $this->respuestaError('HORA_INVALIDA', 'Hora inválida');
        }

        if (empty($datos['diasDeLaSemana'])) {
            return $this->respuestaError('DIAS_REQUERIDOS', 'Selecciona días');
        }

        if (strtolower($datos['modalidad']) === 'presencial' && empty($datos['idAmbiente'])) {
            return $this->respuestaError('AMBIENTE_REQUERIDO', 'Ambiente requerido');
        }

        return null;
    }

    // ================================
    // CONFLICTOS
    // ================================
    private function verificarConflictos(array $datos): void
    {
        $conflictoInstructor = $this->conflictos->detectarConflictoInstructor(
            $datos['idInstructor'],
            $datos['horaInicioClase'],
            $datos['horaFinClase'],
            $datos['diasDeLaSemana'],
            $datos['fechaInicioPeriodo'],
            $datos['fechaFinPeriodo'],
            excluirFicha: $datos['idFicha']
        );

        if ($conflictoInstructor) {
            throw new RuntimeException('Instructor ocupado', 409);
        }

        if (!empty($datos['idAmbiente'])) {
            $conflictoAmbiente = $this->conflictos->detectarConflictoAmbiente(
                $datos['idAmbiente'],
                $datos['horaInicioClase'],
                $datos['horaFinClase'],
                $datos['diasDeLaSemana'],
                $datos['fechaInicioPeriodo'],
                $datos['fechaFinPeriodo'],
                excluirFicha: $datos['idFicha']
            );

            if ($conflictoAmbiente) {
                throw new RuntimeException('Ambiente ocupado', 409);
            }
        }
    }

    // ================================
    // UTILS
    // ================================
    private function resolverAmbiente(array $datos): ?int
    {
        return strtolower($datos['modalidad']) === 'virtual'
            ? null
            : $datos['idAmbiente'];
    }

    private function normalizarDatosEntrada(array $datos): array
    {
        return [
            'idInstructor'       => $datos['idFuncionario']   ?? $datos['id_funcionario']    ?? null,
            'idFicha'            => $datos['idFicha']         ?? $datos['id_ficha']          ?? null,
            'idAmbiente'         => $datos['idAmbiente']      ?? $datos['id_ambiente']       ?? null,
            'modalidad'          => $datos['modalidad']                                      ?? null,
            'estado'             => $datos['estado']                                         ?? 'activo',
            'tipoFormacion'      => $datos['tipoFormacion']   ?? $datos['tipoFormacion']     ?? null,
            'observaciones'      => $datos['observaciones']   ?? $datos['observacion']       ?? null,
            'diasDeLaSemana'     => $datos['dias']                                           ?? [],
            'fechaInicioPeriodo' => $datos['fechaInicio']     ?? $datos['fecha_inicio']      ?? null,
            'fechaFinPeriodo'    => $datos['fechaFin']        ?? $datos['fecha_fin']         ?? null,
            'horaInicioClase'    => $datos['horaInicio']      ?? $datos['hora_inicio']       ?? null,
            'horaFinClase'       => $datos['horaFin']         ?? $datos['hora_fin']          ?? null,
        ];
    }

    private function respuestaError(string $codigo, string $mensaje, int $http = 422): array
    {
        return [
            'ok'      => false,
            'codigo'  => $codigo,
            'mensaje' => $mensaje,
            'http'    => $http,
        ];
    }
}