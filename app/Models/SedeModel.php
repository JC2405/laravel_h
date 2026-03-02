<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use PhpParser\Node\Const_;

class SedeModel extends Model
{

    protected $table = 'sede';
    protected $primaryKey = 'idSede';
    public $timestamps =false;
    protected $fillable = ['nombre','direccion','descripcion','estado','idMunicipio'];
    public const PAGINATION = 10;
}
