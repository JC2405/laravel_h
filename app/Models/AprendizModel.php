<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AprendizModel extends Model
{
    protected $table = "aprendiz";
    protected $primaryKey = 'idAprendiz';
    public $timestamps = false;
    protected $fillable = ['nombre','documento','correo','telefono','password','estado','idFicha'];
    public const PAGINATION = 10;
}
