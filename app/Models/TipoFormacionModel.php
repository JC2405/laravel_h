<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoFormacionModel extends Model
{
    protected $table = 'tipo_formacion';
    protected $primaryKey = 'idTipoFormacion';
    public $timestamps =false;
    protected $fillable = ['nombre','duracion_meses'];
    public const PAGINATOR = 10;
}
