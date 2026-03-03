<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FichaModel extends Model
{
    protected $table = 'ficha';
    protected $primaryKey = 'idFicha';
    public $timestamps = false;
    protected $fillable = ['codigoFicha','jornada','fechaInicio','fechaFin','estado','modalidad','idPrograma','idAmbiente'];
    public const PAGINATION = 10;
}
