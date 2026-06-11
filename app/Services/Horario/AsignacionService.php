<?php

namespace App\Services\Horario;
use Illuminate\Support\Facades\Log;
use App\Exceptions\ConflictoException;
use App\Models\AsignacionModel;
use App\Models\BloqueHorarioModel;
use App\Services\Ficha\FichaService;
use App\Services\Funcionario\FuncionarioService;
use Exception;
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

        } catch (ConflictoException $e) {

            return [
                'ok'         => false,
                'tipo'       => $e->tipo,
                'mensaje'    => $e->mensaje,
                'codigoFicha'=> $e->codigoFicha,
                // Datos que el frontend necesita para mostrar las dos opciones
                'conflicto'  => [
                    'idBloque'   => $e->idBloque,
                    'horaInicio' => $e->horaInicio,
                    'horaFin'    => $e->horaFin,
                ],
                'http'       => 409,
            ];

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

    public function eliminarAsignacionesYBloques(int $idFicha): void
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
    // RESOLVER CONFLICTO — REEMPLAZAR
    // Borra el bloque conflictivo y crea la nueva asignación
    // ================================
    public function resolverReemplazando(int $idBloque, array $datosNuevaAsig): array
    {
        try {
            return DB::transaction(function () use ($idBloque, $datosNuevaAsig) {

                $bloque = BloqueHorarioModel::with('dias')->findOrFail($idBloque);
                $idAsignacion = $bloque->idAsignacion;

                $this->eliminarBloqueCompleto($bloque);

                // Si la asignación quedó sin bloques, también la eliminamos
                if (!BloqueHorarioModel::where('idAsignacion', $idAsignacion)->exists()) {
                    AsignacionModel::where('idAsignacion', $idAsignacion)->delete();
                }

                return $this->crearAsignacion($datosNuevaAsig);
            });

        } catch (Throwable $e) {
            return $this->respuestaError('ERROR', $e->getMessage(), 500);
        }
    }

    // ================================
    // RESOLVER CONFLICTO — PARTIR
    // Acorta el bloque existente y crea la nueva asignación a continuación
    // Ej: Gustavo 06:00-12:00 → queda 06:00-08:00 | nueva ficha 08:00-12:00
    // ================================
  public function resolverPartiendo(int $idBloque, string $nuevaHoraInicio, array $datosNuevaAsig, ?string $nuevaHoraFin = null): array
{
    try {
        return DB::transaction(function () use ($idBloque, $nuevaHoraInicio, $nuevaHoraFin, $datosNuevaAsig) {

            $bloque = BloqueHorarioModel::with('dias')->findOrFail($idBloque);

            if ($nuevaHoraInicio <= $bloque->horaInicio || $nuevaHoraInicio >= $bloque->horaFin) {
                return $this->respuestaError('HORA_INVALIDA', "La hora de corte ({$nuevaHoraInicio}) debe estar entre {$bloque->horaInicio} y {$bloque->horaFin}.", 422);
            }

            $horaFinOriginal  = $bloque->horaFin;
            $diasOriginales   = $bloque->dias->pluck('idDia')->toArray();
            $idAsignacionOrig = $bloque->idAsignacion;

            // 1. Acortar el bloque existente
            $bloque->update(['horaFin' => $nuevaHoraInicio]);

            // 2. ← LÍNEA NUEVA: excluir el bloque recién acortado para que
            //    crearAsignacion no lo detecte como conflicto
            $datosNuevaAsig['excluirBloque'] = $bloque->idBloque;

            // 3. Crear nueva asignación
            $res = $this->crearAsignacion($datosNuevaAsig);

            if (!$res['ok']) {
                return $res;
            }

            $resultado = array_merge($res, [
                'bloqueAcortado' => [
                    'idBloque'        => $bloque->idBloque,
                    'horaInicio'      => $bloque->horaInicio,
                    'horaFinAnterior' => $horaFinOriginal,
                    'horaFinNueva'    => $nuevaHoraInicio,
                ],
            ]);

            // 4. Crear bloque "cola" (10:00 - 11:59) si aplica
            if ($nuevaHoraFin !== null && $nuevaHoraFin < $horaFinOriginal) {
                $bloqueCola = BloqueHorarioModel::create([
                    'idAsignacion'  => $idAsignacionOrig,
                    'fechaInicio'   => $bloque->fechaInicio,
                    'fechaFin'      => $bloque->fechaFin,
                    'horaInicio'    => $nuevaHoraFin,
                    'horaFin'       => $horaFinOriginal,
                    'tipoFormacion' => $bloque->tipoFormacion,
                    'estado'        => $bloque->estado,
                    'observaciones' => $bloque->observaciones,
                ]);

                $bloqueCola->dias()->attach($diasOriginales);

                $resultado['bloqueCola'] = [
                    'idBloque'   => $bloqueCola->idBloque,
                    'horaInicio' => $nuevaHoraFin,
                    'horaFin'    => $horaFinOriginal,
                ];
            }

            return $resultado;
        });

    } catch (Throwable $e) {
        return $this->respuestaError('ERROR', $e->getMessage(), 500);
    }
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
        $asignaciones = AsignacionModel::with(['bloque.dias', 'funcionario', 'ambiente', 'ficha.programa'])
            ->where('idAmbiente', $idAmbiente)
            ->get();

        return [
            'ok'           => true,
            'asignaciones' => $asignaciones,
            'grilla'       => $this->grillaService->construirGrillaParaAmbiente($asignaciones),
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

        $errorFicha = $this->validarFechasDentroDeFicha($datos);
        if ($errorFicha) return $errorFicha;

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
            excluirBloque: $datos['excluirBloque'] ?? null,
            excluirFicha: $datos['idFicha']
        );

        if ($conflictoInstructor) {
            throw new ConflictoException(
                tipo:        'conflicto_instructor',
                mensaje:     sprintf(
                    "El instructor %s ya está asignado en la ficha %s en el horario %s - %s",
                    $conflictoInstructor->instructor_nombre,
                    $conflictoInstructor->codigoFicha,
                    $conflictoInstructor->horaInicio,
                    $conflictoInstructor->horaFin
                ),
                codigoFicha: $conflictoInstructor->codigoFicha,
                idBloque:    $conflictoInstructor->idBloque,
                horaInicio:  $conflictoInstructor->horaInicio,
                horaFin:     $conflictoInstructor->horaFin,
            );
        }

        if (!empty($datos['idAmbiente'])) {
            $conflictoAmbiente = $this->conflictos->detectarConflictoAmbiente(
                $datos['idAmbiente'],
                $datos['horaInicioClase'],
                $datos['horaFinClase'],
                $datos['diasDeLaSemana'],
                $datos['fechaInicioPeriodo'],
                $datos['fechaFinPeriodo'],
                excluirFicha: $datos['idFicha'],
                excluirBloque: $datos['excluirBloque'] ?? null
            );

            if ($conflictoAmbiente) {
                throw new ConflictoException(
                    tipo:        'conflicto_ambiente',
                    mensaje:     sprintf(
                        "El ambiente está ocupado en la ficha %s en el horario %s - %s",
                        $conflictoAmbiente->codigoFicha,
                        $conflictoAmbiente->horaInicio,
                        $conflictoAmbiente->horaFin
                    ),
                    codigoFicha: $conflictoAmbiente->codigoFicha,
                    idBloque:    $conflictoAmbiente->idBloque,
                    horaInicio:  $conflictoAmbiente->horaInicio,
                    horaFin:     $conflictoAmbiente->horaFin,
                );
            }
        }
         if (strtolower(trim($datos['tipoFormacion'] ?? '')) === 'titulada') {

          $conflictoTipoFormacion = $this->conflictos->detectarConflictoTitulada(
              $datos['idFicha'],
              $datos['horaInicioClase'],
              $datos['horaFinClase'],
              $datos['fechaInicioPeriodo'],
              $datos['fechaFinPeriodo'],
              excluirBloque: $datos['excluirBloque'] ?? null
          );

           if ($conflictoTipoFormacion) {
                throw new ConflictoException(
                    tipo:        'conflicto_ambiente',  
                    mensaje:     sprintf(
                        "La ficha %s ya tiene asignado formacion Titulada en el horario %s - %s",
                        $conflictoTipoFormacion->codigoFicha,
                        $conflictoTipoFormacion->horaInicio,
                        $conflictoTipoFormacion->horaFin
                        
                    ),
                    codigoFicha: $conflictoTipoFormacion->codigoFicha,
                    idBloque:    $conflictoTipoFormacion->idBloque,
                    horaInicio:  $conflictoTipoFormacion->horaInicio,
                    horaFin:     $conflictoTipoFormacion->horaFin,
                );
            }
      }
    }

    public function validarFechasDentroDeFicha(array $datos): ?array
    {
        $ficha = $this->fichaService->findById($datos['idFicha']);

        if (!$ficha) {
            return $this->respuestaError('FICHA_NO_ENCONTRADA', 'La ficha no existe.', 404);
        }

        $inicioBloque = $datos['fechaInicioPeriodo'];
        $finBloque    = $datos['fechaFinPeriodo'];
        $inicioFicha  = $ficha->fechaInicio;
        $finFicha     = $ficha->fechaFin;

        $dentroDelRango = $inicioBloque >= $inicioFicha && $finBloque <= $finFicha;

        if (!$dentroDelRango) {
            return $this->respuestaError(
                'FECHA_FUERA_DE_RANGO',
                "Las fechas del horario ({$inicioBloque} → {$finBloque}) deben estar dentro del período de la ficha ({$inicioFicha} → {$finFicha}).",
                422
            );
        }

        return null;
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
            'idInstructor'       => $datos['idFuncionario']   ?? $datos['id_funcionario']  ?? null,
            'idFicha'            => $datos['idFicha']         ?? $datos['id_ficha']        ?? null,
            'idAmbiente'         => $datos['idAmbiente']      ?? $datos['id_ambiente']     ?? null,
            'modalidad'          => $datos['modalidad']                                    ?? null,
            'estado'             => $datos['estado']                                       ?? 'activo',
            'tipoFormacion'      => $datos['tipoFormacion']                                ?? null,
            'observaciones'      => $datos['observaciones']   ?? $datos['observacion']     ?? null,
            'diasDeLaSemana'     => $datos['dias']                                         ?? [],
            'fechaInicioPeriodo' => $datos['fechaInicio']     ?? $datos['fecha_inicio']    ?? null,
            'fechaFinPeriodo'    => $datos['fechaFin']        ?? $datos['fecha_fin']       ?? null,
            'horaInicioClase'    => $datos['horaInicio']      ?? $datos['hora_inicio']     ?? null,
            'horaFinClase'       => $datos['horaFin']         ?? $datos['hora_fin']        ?? null,
            'excluirBloque' => $datos['excluirBloque'] ?? null,
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

  public function dashboardCharts(int $anio): array
{
    // ── Horarios por mes (año filtrado) ──────────────────────────────
    $porMes = DB::table('bloque as b')                                    // ← era bloque_horario
        ->join('asignacion as a', 'a.idAsignacion', '=', 'b.idAsignacion')
        ->whereYear('b.fechaInicio', $anio)
        ->selectRaw('MONTH(b.fechaInicio) as mes, COUNT(DISTINCT a.idAsignacion) as total')
        ->groupByRaw('MONTH(b.fechaInicio)')
        ->orderBy('mes')
        ->get()
        ->keyBy('mes');

    $meses = [];
    for ($m = 1; $m <= 12; $m++) {
        $meses[] = (int) ($porMes[$m]->total ?? 0);
    }

    // ── Fichas con/sin horarios ──────────────────────────────────────
    $totalFichas      = DB::table('ficha')->count();
    $fichasConHorario = DB::table('ficha')
        ->whereExists(fn($q) =>
            $q->select(DB::raw(1))
              ->from('asignacion')
              ->whereColumn('asignacion.idFicha', 'ficha.idFicha')        // ← whereColumn necesita select
        )
        ->count();
    $fichasSinHorario = $totalFichas - $fichasConHorario;

    // ── Años disponibles ─────────────────────────────────────────────
    $aniosDisponibles = DB::table('bloque')                               // ← era bloque_horario
        ->selectRaw('DISTINCT YEAR(fechaInicio) as anio')
        ->orderByDesc('anio')
        ->pluck('anio');

    return [
        'horarios_por_mes'   => $meses,
        'fichas_con_horario' => $fichasConHorario,
        'fichas_sin_horario' => $fichasSinHorario,
        'anios_disponibles'  => $aniosDisponibles,
        'anio_consultado'    => $anio,
    ];
    }
}