<?php

namespace App\Exceptions;

use RuntimeException;

class ConflictoException extends RuntimeException
{
    public function __construct(
        public readonly string $tipo,
        public readonly string $mensaje,
        public readonly string $codigoFicha,
        public readonly int    $idBloque,
        public readonly string $horaInicio,
        public readonly string $horaFin,
    ) {
        parent::__construct($mensaje);
    }
}