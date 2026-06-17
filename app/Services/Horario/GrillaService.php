<?php

namespace App\Services\Horario;

class GrillaService
{
    public const RELACIONES = [
        'bloque.dias', 'funcionario', 'ambiente', 'ficha.programa', 'ficha.sede.municipio',
    ];

    public function construirGrillaParaFicha($asignaciones): array
    {
        return $this->construirGrilla($asignaciones, function ($asignacion, $bloque) {
            return [
                'ficha'        => $asignacion->ficha?->numero ?? '—',
                'programa'     => $asignacion->ficha?->programa?->nombre ?? '—',
                'instructor'   => $asignacion->funcionario?->nombre ?? '—',
                'ambiente'     => $this->formatearAmbiente($asignacion),
                'modalidad'    => $asignacion->modalidad,
                'fechaInicio'  => $bloque->fechaInicio,
                'fechaFin'     => $bloque->fechaFin,
                'idBloque'     => $bloque->idBloque,
                'idAsignacion' => $asignacion->idAsignacion,
            ];
        });
    }

    public function construirGrillaParaAmbiente($asignaciones):array
    {
        return $this->construirGrilla($asignaciones, function($asignacion, $bloque){
            return [
                 'ficha'        => $asignacion->ficha?->numero ?? '—',
                'programa'     => $asignacion->ficha?->programa?->nombre ?? '—',
                'instructor'   => $asignacion->funcionario?->nombre ?? '—',
                'ambiente'     => $this->formatearAmbiente($asignacion),
                'modalidad'    => $asignacion->modalidad,
                'fechaInicio'  => $bloque->fechaInicio,
                'fechaFin'     => $bloque->fechaFin,
                'idBloque'     => $bloque->idBloque,
                'idAsignacion' => $asignacion->idAsignacion,
            ];
        });
    }

    public function construirGrillaParaAprendices($asignaciones): array
    {
        return $this->construirGrilla($asignaciones, function ($asignacion, $bloque) {
            return [
                'ficha'        => $asignacion->ficha?->numero ?? '—',
                'programa'     => $asignacion->ficha?->programa?->nombre ?? '—',
                'instructor'   => $asignacion->funcionario?->nombre ?? '—',
                'ambiente'     => $this->formatearAmbiente($asignacion),
                'modalidad'    => $asignacion->modalidad,
                'sede'         => $asignacion->ficha?->sede?->nombre ?? null,
                'municipio'    => $asignacion->ficha?->sede?->municipio?->nombreMunicipio ?? null,
                'fechaInicio'  => $bloque->fechaInicio,
                'fechaFin'     => $bloque->fechaFin,
                'idBloque'     => $bloque->idBloque,
                'idAsignacion' => $asignacion->idAsignacion,
            ];
        });
    }

    public function construirGrillaParaInstructor($asignaciones): array
    {
        return $this->construirGrilla($asignaciones, function ($asignacion, $bloque) {
            return [
                'ficha'        => $asignacion->ficha ? 'Ficha ' . $asignacion->ficha->codigoFicha : '—',
                'programa'     => $asignacion->ficha?->programa?->nombre ?? '—',
                'ambiente'     => $asignacion->ambiente?->codigo ?? '-',
                'modalidad'    => $asignacion->modalidad,
                'sede'         => $asignacion->ficha?->sede?->nombre ?? null,
                'municipio'    => $asignacion->ficha?->sede?->municipio?->nombreMunicipio ?? null,
                'fechaInicio'  => $bloque->fechaInicio,
                'fechaFin'     => $bloque->fechaFin,
                'idBloque'     => $bloque->idBloque,
                'idAsignacion' => $asignacion->idAsignacion,
            ];
        });
    }

   

    public function construirGrilla($asignaciones, callable $mapearCelda): array
    {
        $franjas = $this->generarFranjasHorarias();
        $grilla  = array_fill_keys($franjas, []);

        foreach ($asignaciones as $asignacion) {
            $bloque = $asignacion->bloque;
            if (!$bloque) continue;

            $horaInicio = $bloque->horaInicio ?? $bloque->hora_inicio ?? null;
            $horaFin    = $bloque->horaFin    ?? $bloque->hora_fin    ?? null;
            if (!$horaInicio || !$horaFin) continue;

            foreach ($franjas as $franja) {
                if (!$this->bloqueSeSOlapaConFranja($franja, $horaInicio, $horaFin)) continue;

                foreach ($bloque->dias as $dia) {
                    $nombreDia = $dia->nombreDia ?? $dia->nombre ?? null;
                    if (!$nombreDia || isset($grilla[$franja][$nombreDia])) continue;

                    $grilla[$franja][$nombreDia] = $mapearCelda($asignacion, $bloque);
                }
            }
        }

        $grilla = array_filter($grilla, fn($celdas) => !empty($celdas));


        return $grilla;
    }



    // Aqui se pone 
    public function formatearAmbiente($asignacion): string
    {
        return $asignacion->ambiente
            ? $asignacion->ambiente->codigo . ' - No.' . ($asignacion->ambiente->numero ?? '')
            : '';
    }




    //Espacio para poner franja desde que horas hasta que horas se genera la grilla
    public function generarFranjasHorarias(): array
    {
        $franjas = [];

        // Aqui se maneja el inicio de la grilla desde que horas hasta que horas // $hora es para partir horas de 2 en 2 
        for ($hora = 6; $hora < 24; $hora += 1) {
            $franjas[] = sprintf('%02d:00 - %02d:00', $hora, $hora + 1);
        }
        return $franjas;
    }




    private function bloqueSeSOlapaConFranja(string $franja, string $horaInicio, string $horaFin): bool
    {
        [$inicioFranja, $finFranja] = explode(' - ', $franja);

        return strtotime($horaInicio) < strtotime($finFranja)
            && strtotime($horaFin)    > strtotime($inicioFranja);
    }
}




