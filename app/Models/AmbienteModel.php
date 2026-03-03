<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmbienteModel extends Model
{
    protected $table = 'ambiente';
    protected $primaryKey = 'idAmbiente';
    public $timestamps =false;
    protected $fillable = ['codigo','capacidad','numero','descripcion','bloque','estado','tipoAmbiente','idSede','idArea'];
    public const PAGINATION=10;
}