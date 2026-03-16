<?php

namespace App\Services\Horario;

use App\Models\AsignacionModel;
use App\Models\BloqueHorarioModel;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * AsignacionService
 *
 * Gestiona la creación, eliminación y consulta de asignaciones horarias.
 *
 * Una "asignación" une:
 *   - un instructor (funcionario)
 *   - una ficha (grupo de aprendices)
 *   - opcionalmente un ambiente (aula)
 *
 * Cada asignación tiene exactamente UN bloque horario, y ese bloque
 * puede ocurrir en VARIOS días de la semana (lunes, miércoles, viernes…).
 */
class AsignacionService
{
    public function __construct(
        protected DeteccionConflictoService $servicioConflictos,
    ) {}

    // =========================================================================
    //  NORMALIZACIÓN DE DATOS ENTRANTES
    // =========================================================================

    /**
     * Normaliza los datos del request aceptando tanto camelCase como snake_case.
     *
     * El frontend puede enviar "idFuncionario" o "id_funcionario" —
     * esta función unifica ambas variantes en un solo array con nombres claros.
     *
     * @param  array $datosOriginales  Datos crudos que llegan del request
     * @return array                   Datos normalizados con claves consistentes
     */
    private function normalizarDatosEntrada(array $datosOriginales): array
    {
        return [
            // ── Quién imparte la clase ────────────────────────────────────────
            'idInstructor'      => $datosOriginales['idFuncionario']   ?? $datosOriginales['id_funcionario']    ?? null,

            // ── A qué ficha (grupo) pertenece ────────────────────────────────
            'idFicha'           => $datosOriginales['idFicha']         ?? $datosOriginales['id_ficha']          ?? null,

            // ── Dónde se dicta (null si es virtual) ──────────────────────────
            'idAmbiente'        => $datosOriginales['idAmbiente']      ?? $datosOriginales['id_ambiente']       ?? null,

            // ── Cómo se dicta: "presencial" o "virtual" ───────────────────────
            'modalidad'         => $datosOriginales['modalidad']                                                ?? null,

            // ── Estado inicial del registro ───────────────────────────────────
            'estado'            => $datosOriginales['estado']                                                   ?? 'activo',

            // ── Nota libre sobre la clase ─────────────────────────────────────
            'observaciones'     => $datosOriginales['observaciones']   ?? $datosOriginales['observacion']       ?? null,

            // ── Qué días de la semana ocurre (array de idDia) ────────────────
            'diasDeLaSemana'    => $datosOriginales['dias']                                                     ?? [],

            // ── Rango de fechas del período de formación ──────────────────────
            'fechaInicioPeriodo' => $datosOriginales['fechaInicio']    ?? $datosOriginales['fecha_inicio']      ?? null,
            'fechaFinPeriodo'    => $datosOriginales['fechaFin']       ?? $datosOriginales['fecha_fin']         ?? null,

            // ── Franja horaria diaria ─────────────────────────────────────────
            'horaInicioClase'   => $datosOriginales['horaInicio']      ?? $datosOriginales['hora_inicio']       ?? null,
            'horaFinClase'      => $datosOriginales['horaFin']         ?? $datosOriginales['hora_fin']          ?? null,

            // ── Tipo de programa: "Titulada" o "Formativa" ────────────────────
            'tipoFormacion'     => $datosOriginales['tipoDeFormacion'] ?? $datosOriginales['tipo_de_formacion'] ?? null,
        ];
    }

    // =========================================================================
    //  CREAR ASIGNACIÓN
    // =========================================================================

    /**
     * Crea una nueva asignación horaria con su bloque y días asociados.
     *
     * Flujo:
     *  1. Normaliza los datos de entrada
     *  2. Valida reglas de negocio (fechas, horas, ambiente obligatorio si presencial)
     *  3. Verifica que el instructor no tenga otra clase en ese horario
     *  4. Verifica que el ambiente no esté ocupado (si es presencial)
     *  5. Crea la asignación, el bloque y asocia los días — todo en una transacción
     *
     * @param  array $datosEntrada  Datos validados que llegan del request
     * @return array  ['ok' => true, 'asignacion' => AsignacionModel] o ['ok' => false, 'codigo' => ..., 'mensaje' => ...]
     */
    public function crearAsignacion(array $datosEntrada): array
    {
        // Unificar camelCase y snake_case en nombres descriptivos
        $datos = $this->normalizarDatosEntrada($datosEntrada);

        // ── Validaciones de reglas de negocio ─────────────────────────────────

        if ($datos['fechaInicioPeriodo'] > $datos['fechaFinPeriodo']) {
            return $this->respuestaError(
                codigo:  'FECHA_INVALIDA',
                mensaje: 'La fecha de inicio no puede ser mayor que la fecha fin.'
            );
        }

        if ($datos['horaInicioClase'] >= $datos['horaFinClase']) {
            return $this->respuestaError(
                codigo:  'HORA_INVALIDA',
                mensaje: 'La hora de inicio debe ser menor a la hora fin.'
            );
        }

        if (empty($datos['diasDeLaSemana'])) {
            return $this->respuestaError(
                codigo:  'DIAS_REQUERIDOS',
                mensaje: 'Debes seleccionar al menos un día de la semana.'
            );
        }

        // Normalizar la modalidad a minúsculas para comparaciones seguras
        $modalidadNormalizada = strtolower(trim($datos['modalidad'] ?? ''));

        if ($modalidadNormalizada === 'presencial' && empty($datos['idAmbiente'])) {
            return $this->respuestaError(
                codigo:  'AMBIENTE_REQUERIDO',
                mensaje: 'El ambiente es requerido para la modalidad presencial.'
            );
        }

        // ── Todo lo que toca la BD va dentro de una transacción ───────────────
        try {
            $asignacionCreada = DB::transaction(function () use ($datos, $modalidadNormalizada) {

                // El ambiente solo se guarda si la clase es presencial
                $idAmbienteAGuardar = $modalidadNormalizada === 'presencial'
                    ? ($datos['idAmbiente'] ?? null)
                    : null;

                // ── Verificar que el instructor esté libre en ese horario ─────
                $conflictoDeInstructor = $this->servicioConflictos->detectarConflictoInstructor(
                    $datos['idInstructor'],
                    $datos['horaInicioClase'],
                    $datos['horaFinClase'],
                    $datos['diasDeLaSemana'],
                    $datos['fechaInicioPeriodo'],
                    $datos['fechaFinPeriodo'],
                    excluirFicha: $datos['idFicha'] ?? null
                );

                if ($conflictoDeInstructor) {
                    $horaInicioDelConflicto = substr($conflictoDeInstructor->horaInicio, 0, 5);
                    $horaFinDelConflicto    = substr($conflictoDeInstructor->horaFin, 0, 5);
                    $fichaDelConflicto      = $conflictoDeInstructor->codigoFicha;
                    $nombreInstructor       = $conflictoDeInstructor->instructor_nombre;

                    throw new RuntimeException(
                        "El instructor {$nombreInstructor} ya tiene clase"
                        . " de {$horaInicioDelConflicto} a {$horaFinDelConflicto}"
                        . " (Ficha {$fichaDelConflicto})"
                        . " — no puede tener dos fichas en el mismo horario.",
                        409
                    );
                }

                // ── Verificar que el ambiente esté libre (solo presencial) ────
                if ($idAmbienteAGuardar) {
                    $conflictoDeAmbiente = $this->servicioConflictos->detectarConflictoAmbiente(
                        $idAmbienteAGuardar,
                        $datos['horaInicioClase'],
                        $datos['horaFinClase'],
                        $datos['diasDeLaSemana'],
                        $datos['fechaInicioPeriodo'],
                        $datos['fechaFinPeriodo'],
                        excluirFicha: $datos['idFicha'] ?? null
                    );

                    if ($conflictoDeAmbiente) {
                        $horaInicioDelConflicto = substr($conflictoDeAmbiente->horaInicio, 0, 5);
                        $horaFinDelConflicto    = substr($conflictoDeAmbiente->horaFin, 0, 5);
                        $fichaDelConflicto      = $conflictoDeAmbiente->codigoFicha;

                        throw new RuntimeException(
                            "El ambiente ya está ocupado de {$horaInicioDelConflicto} a {$horaFinDelConflicto}"
                            . " (Ficha {$fichaDelConflicto})"
                            . " — no se puede usar el mismo ambiente para otra ficha en ese horario.",
                            409
                        );
                    }
                }

                // ── Paso 1: crear el registro de asignación ───────────────────
                $nuevaAsignacion = AsignacionModel::create([
                    'idFuncionario' => $datos['idInstructor'],
                    'idFicha'       => $datos['idFicha'],
                    'idAmbiente'    => $idAmbienteAGuardar,
                    'modalidad'     => $modalidadNormalizada,
                    'estado'        => $datos['estado'],
                ]);

                // ── Paso 2: crear el bloque horario ligado a la asignación ────
                $nuevoBloqueHorario = BloqueHorarioModel::create([
                    'idAsignacion'  => $nuevaAsignacion->idAsignacion,
                    'fechaInicio'   => $datos['fechaInicioPeriodo'],
                    'fechaFin'      => $datos['fechaFinPeriodo'],
                    'horaInicio'    => $datos['horaInicioClase'],
                    'horaFin'       => $datos['horaFinClase'],
                    'estado'        => $datos['estado'],
                    'observaciones' => $datos['observaciones'],
                ]);

                // ── Paso 3: asociar los días de la semana al bloque ───────────
                // Inserta una fila en la tabla pivot "bloqueDia" por cada día
                $nuevoBloqueHorario->dias()->attach($datos['diasDeLaSemana']);

                // Retornar la asignación con todas sus relaciones para la respuesta
                return $nuevaAsignacion->load([
                    'bloque.dias',
                    'funcionario',
                    'ambiente',
                    'ficha.programa',
                ]);
            });

            return ['ok' => true, 'asignacion' => $asignacionCreada];

        } catch (RuntimeException $excepcionControlada) {
            // Errores de negocio esperados: conflictos de horario, ambiente ocupado, etc.
            return $this->respuestaError(
                codigo:  $excepcionControlada->getCode() === 409 ? 'CONFLICTO' : 'ERROR',
                mensaje: $excepcionControlada->getMessage(),
                http:    $excepcionControlada->getCode() === 409 ? 409 : 422
            );
        } catch (\Throwable $excepcionInesperada) {
            // Errores no anticipados: fallo de BD, null inesperado, etc.
            return $this->respuestaError(
                codigo:  'ERROR_INTERNO',
                mensaje: 'Ocurrió un error inesperado: ' . $excepcionInesperada->getMessage()
            );
        }
    }

    // =========================================================================
    //  ELIMINAR ASIGNACIÓN COMPLETA
    // =========================================================================

    /**
     * Elimina una asignación junto con su bloque horario y días asociados.
     *
     * Orden de eliminación (respeta integridad referencial):
     *  1. Desasociar los días del bloque (tabla pivot bloqueDia)
     *  2. Eliminar el bloque horario
     *  3. Eliminar la asignación
     *
     * @param  int   $idAsignacion  ID de la asignación a eliminar
     * @return array
     */
    public function eliminarAsignacion(int $idAsignacion): array
    {
        // Traer la asignación con su bloque para poder eliminar ambos
        $asignacionAEliminar = AsignacionModel::with('bloque')->find($idAsignacion);

        if (!$asignacionAEliminar) {
            return $this->respuestaError(
                codigo:  'NO_ENCONTRADO',
                mensaje: 'Asignación no encontrada.',
                http:    404
            );
        }

        DB::transaction(function () use ($asignacionAEliminar) {
            if ($asignacionAEliminar->bloque) {
                // Primero limpiar la tabla pivot, luego el bloque
                $asignacionAEliminar->bloque->dias()->detach();
                $asignacionAEliminar->bloque->delete();
            }
            $asignacionAEliminar->delete();
        });

        return ['ok' => true, 'mensaje' => 'Asignación y bloque eliminados correctamente.'];
    }

    // =========================================================================
    //  ELIMINAR UN DÍA DEL BLOQUE
    // =========================================================================

    /**
     * Elimina un día específico de un bloque horario.
     *
     * Hay dos casos posibles:
     *
     *  CASO A — El bloque tiene MÁS de un día:
     *    Solo se desasocia ese día de la tabla pivot "bloqueDia".
     *    El bloque y la asignación quedan intactos con los días restantes.
     *    → Respuesta: accion = 'DIA_ELIMINADO'
     *
     *  CASO B — El bloque tiene EXACTAMENTE ese único día:
     *    No tiene sentido conservar un bloque sin días, así que se elimina
     *    en cascada: día → bloque → asignación.
     *    → Respuesta: accion = 'ASIGNACION_ELIMINADA'
     *
     * @param  int   $idBloque  ID del bloque horario (tabla "bloque")
     * @param  int   $idDia     ID del día a eliminar (tabla "dia")
     * @return array
     */
    public function eliminarDiaDeBloque(int $idBloque, int $idDia): array
    {
        // Cargar el bloque con sus días para saber cuántos tiene actualmente
        $bloqueHorario = BloqueHorarioModel::with('dias')->find($idBloque);

        if (!$bloqueHorario) {
            return $this->respuestaError(
                codigo:  'NO_ENCONTRADO',
                mensaje: 'Bloque horario no encontrado.',
                http:    404
            );
        }

        // Extraer solo los IDs de los días que tiene este bloque ahora mismo
        $idsDeLosDiasActuales = $bloqueHorario->dias->pluck('idDia')->toArray();

        // Verificar que el día que se quiere eliminar realmente pertenece al bloque
        $elDiaPerteneceAlBloque = in_array($idDia, $idsDeLosDiasActuales);

        if (!$elDiaPerteneceAlBloque) {
            return $this->respuestaError(
                codigo:  'DIA_NO_PERTENECE',
                mensaje: 'El día indicado no pertenece a este bloque horario.',
                http:    422
            );
        }

        $cantidadDiasQuetieneElBloque = count($idsDeLosDiasActuales);

        // ── CASO B: es el último día → eliminar todo en cascada ───────────────
        if ($cantidadDiasQuetieneElBloque === 1) {
            DB::transaction(function () use ($bloqueHorario) {
                // Guardar el ID antes de eliminar el bloque
                $idAsignacionRelacionada = $bloqueHorario->idAsignacion;

                // 1. Limpiar la tabla pivot bloqueDia
                $bloqueHorario->dias()->detach();

                // 2. Eliminar el bloque horario
                $bloqueHorario->delete();

                // 3. Eliminar la asignación que quedó sin bloque (ya no tiene sentido)
                AsignacionModel::where('idAsignacion', $idAsignacionRelacionada)->delete();
            });

            return [
                'ok'      => true,
                'accion'  => 'ASIGNACION_ELIMINADA',
                'mensaje' => 'Era el único día del bloque. Se eliminó la asignación completa.',
            ];
        }

        // ── CASO A: quedan más días → solo quitar ese día del pivot ───────────
        DB::transaction(function () use ($bloqueHorario, $idDia) {
            // detach($idDia) elimina solo esa fila de la tabla pivot bloqueDia
            $bloqueHorario->dias()->detach($idDia);
        });

        return [
            'ok'      => true,
            'accion'  => 'DIA_ELIMINADO',
            'mensaje' => 'Día eliminado correctamente del bloque horario.',
        ];
    }

    // =========================================================================
    //  CONSULTAS / LISTADOS
    // =========================================================================

    /**
     * Retorna todas las asignaciones de una ficha con su grilla horaria.
     *
     * La "grilla" es una estructura lista para pintar el calendario en el frontend:
     *  {
     *    "06:00 - 08:00": {
     *       "Lunes":   { instructor, ambiente, fechaInicio, ... },
     *       "Martes":  { ... }
     *    },
     *    "08:00 - 10:00": { ... }
     *  }
     *
     * @param  int $idFicha  ID de la ficha (grupo de aprendices)
     * @return array
     */
    public function listarAsignacionesPorFicha(int $idFicha): array
    {
        $asignacionesDeLaFicha = AsignacionModel::with([
                'bloque.dias',
                'funcionario',
                'ambiente',
                'ficha.programa',
            ])
            ->where('idFicha', $idFicha)
            ->orderByDesc('idAsignacion')
            ->get();

        return [
            'ok'           => true,
            'asignaciones' => $asignacionesDeLaFicha,
            'grilla'       => $this->construirGrillaParaFicha($asignacionesDeLaFicha),
        ];
    }

    /**
     * Retorna todas las asignaciones de un instructor con su grilla horaria.
     *
     * @param  int $idInstructor  ID del funcionario (instructor)
     * @return array
     */
    public function listarClasesPorInstructor(int $idInstructor): array
    {
        $asignacionesDelInstructor = AsignacionModel::with([
                'bloque.dias',
                'ambiente',
                'funcionario',
                'ficha.programa',
            ])
            ->where('idFuncionario', $idInstructor)
            ->get();

        return [
            'clases' => $asignacionesDelInstructor->values()->all(),
            'grilla' => $this->construirGrillaParaInstructor($asignacionesDelInstructor),
        ];
    }

    // =========================================================================
    //  CONSTRUCCIÓN DE GRILLAS
    // =========================================================================

    /**
     * Construye la grilla horaria vista desde el ángulo de una FICHA (coordinador).
     *
     * Cada celda muestra quién enseña, en qué ambiente y cuándo,
     * más los IDs necesarios para que el frontend ofrezca botones de eliminación.
     *
     * @param  \Illuminate\Support\Collection $asignacionesDeLaFicha
     * @return array  Grilla [ franja => [ dia => datosDeLaCelda ] ]
     */
    public function construirGrillaParaFicha($asignacionesDeLaFicha): array
    {
        $franjasDisponibles = $this->generarFranjasHorarias();

        // Inicializar cada franja con un array vacío
        $grilla = array_fill_keys($franjasDisponibles, []);

        foreach ($asignacionesDeLaFicha as $asignacion) {
            $bloqueHorario = $asignacion->bloque;
            if (!$bloqueHorario) continue;

            // Aceptar tanto camelCase como snake_case del modelo
            $horaInicioDelBloque = $bloqueHorario->horaInicio ?? $bloqueHorario->hora_inicio ?? null;
            $horaFinDelBloque    = $bloqueHorario->horaFin    ?? $bloqueHorario->hora_fin    ?? null;
            if (!$horaInicioDelBloque || !$horaFinDelBloque) continue;

            $fechaInicioDelBloque = $bloqueHorario->fechaInicio ?? $bloqueHorario->fecha_inicio ?? null;
            $fechaFinDelBloque    = $bloqueHorario->fechaFin    ?? $bloqueHorario->fecha_fin    ?? null;

            foreach ($franjasDisponibles as $franja) {
                // Si el bloque no cae dentro de esta franja, pasar a la siguiente
                if (!$this->bloqueSeSOlapaConFranja($franja, $horaInicioDelBloque, $horaFinDelBloque)) {
                    continue;
                }

                foreach ($bloqueHorario->dias as $dia) {
                    $nombreDelDia = $dia->nombreDia ?? $dia->nombre ?? null;
                    if (!$nombreDelDia) continue;

                    // No sobreescribir si ya se llenó esa celda
                    if (isset($grilla[$franja][$nombreDelDia])) continue;

                    // Descripción del ambiente o "Virtual" si no hay aula
                    $descripcionDelAmbiente = $asignacion->ambiente
                        ? ($asignacion->ambiente->codigo . ' - No.' . ($asignacion->ambiente->numero ?? ''))
                        : 'Virtual';

                    $grilla[$franja][$nombreDelDia] = [
                        'instructor'   => $asignacion->funcionario->nombre ?? '—',
                        'ambiente'     => $descripcionDelAmbiente,
                        'modalidad'    => $asignacion->modalidad,
                        'fechaInicio'  => $fechaInicioDelBloque,
                        'fechaFin'     => $fechaFinDelBloque,
                        'idBloque'     => $bloqueHorario->idBloque,
                        'idAsignacion' => $asignacion->idAsignacion,
                    ];
                }
            }
        }

        return $grilla;
    }

    /**
     * Construye la grilla horaria vista desde el ángulo del INSTRUCTOR.
     *
     * Cada celda muestra a qué ficha y programa pertenece la clase,
     * y en qué ambiente o si es virtual.
     *
     * @param  \Illuminate\Support\Collection $asignacionesDelInstructor
     * @return array  Grilla [ franja => [ dia => datosDeLaCelda ] ]
     */
    public function construirGrillaParaInstructor($asignacionesDelInstructor): array
    {
        $franjasDisponibles = $this->generarFranjasHorarias();
        $grilla             = array_fill_keys($franjasDisponibles, []);

        foreach ($asignacionesDelInstructor as $asignacion) {
            $bloqueHorario = $asignacion->bloque;
            if (!$bloqueHorario) continue;

            $horaInicioDelBloque = $bloqueHorario->horaInicio ?? null;
            $horaFinDelBloque    = $bloqueHorario->horaFin    ?? null;
            if (!$horaInicioDelBloque || !$horaFinDelBloque) continue;

            $fechaInicioDelBloque = $bloqueHorario->fechaInicio ?? null;
            $fechaFinDelBloque    = $bloqueHorario->fechaFin    ?? null;

            foreach ($franjasDisponibles as $franja) {
                if (!$this->bloqueSeSOlapaConFranja($franja, $horaInicioDelBloque, $horaFinDelBloque)) {
                    continue;
                }

                foreach ($bloqueHorario->dias as $dia) {
                    $nombreDelDia = $dia->nombreDia ?? $dia->nombre ?? null;
                    if (!$nombreDelDia) continue;

                    if (isset($grilla[$franja][$nombreDelDia])) continue;

                    // Código de la ficha o "—" si no hay ficha
                    $etiquetaDeLaFicha = $asignacion->ficha
                        ? ('Ficha ' . ($asignacion->ficha->codigoFicha ?? ''))
                        : '—';

                    $grilla[$franja][$nombreDelDia] = [
                        'ficha'        => $etiquetaDeLaFicha,
                        'programa'     => $asignacion->ficha?->programa?->nombre ?? '—',
                        'ambiente'     => $asignacion->ambiente ? $asignacion->ambiente->codigo : 'Virtual',
                        'modalidad'    => $asignacion->modalidad,
                        'fechaInicio'  => $fechaInicioDelBloque,
                        'fechaFin'     => $fechaFinDelBloque,
                        'idBloque'     => $bloqueHorario->idBloque,
                        'idAsignacion' => $asignacion->idAsignacion,
                    ];
                }
            }
        }

        return $grilla;
    }

    // =========================================================================
    //  MÉTODOS DE APOYO (PRIVADOS)
    // =========================================================================

    /**
     * Genera las franjas de 2 horas que cubre el horario del centro:
     * de las 06:00 hasta las 24:00.
     *
     * Resultado: ["06:00 - 08:00", "08:00 - 10:00", ..., "22:00 - 24:00"]
     *
     * @return string[]
     */
    private function generarFranjasHorarias(): array
    {
        $listaDeFranjas = [];

        for ($horaActual = 6; $horaActual < 24; $horaActual += 2) {
            $inicioDeFranja  = sprintf('%02d:00', $horaActual);
            $finDeFranja     = sprintf('%02d:00', $horaActual + 2);
            $listaDeFranjas[] = "{$inicioDeFranja} - {$finDeFranja}";
        }

        return $listaDeFranjas;
    }

    /**
     * Determina si un bloque horario se solapa con una franja de la grilla.
     *
     * Usa el algoritmo clásico de solapamiento de intervalos:
     *   inicio_bloque < fin_franja  AND  fin_bloque > inicio_franja
     *
     * Ejemplo:
     *   franja = "08:00 - 10:00", bloque = 07:00 a 09:00
     *   → 07:00 < 10:00  AND  09:00 > 08:00  → true (se solapa)
     *
     * @param  string $franja           Formato "HH:MM - HH:MM"
     * @param  string $horaInicioBloque Hora de inicio del bloque (ej. "07:00:00")
     * @param  string $horaFinBloque    Hora de fin del bloque    (ej. "09:00:00")
     * @return bool
     */
    private function bloqueSeSOlapaConFranja(
        string $franja,
        string $horaInicioBloque,
        string $horaFinBloque
    ): bool {
        [$horaInicioDeFranja, $horaFinDeFranja] = explode(' - ', $franja);

        $timestampInicioBloque = strtotime($horaInicioBloque);
        $timestampFinBloque    = strtotime($horaFinBloque);
        $timestampInicioFranja = strtotime($horaInicioDeFranja);
        $timestampFinFranja    = strtotime($horaFinDeFranja);

        return $timestampInicioBloque < $timestampFinFranja
            && $timestampFinBloque    > $timestampInicioFranja;
    }

    /**
     * Construye un array de respuesta de error uniforme.
     *
     * Centralizar esto evita repetir el mismo array en cada return del service
     * y garantiza que todos los errores tengan siempre las mismas claves.
     *
     * @param  string $codigo   Código legible por el frontend (ej. 'CONFLICTO', 'NO_ENCONTRADO')
     * @param  string $mensaje  Mensaje en español para mostrar al usuario
     * @param  int    $http     Código HTTP sugerido (por defecto 422 = Unprocessable)
     * @return array
     */
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