<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuncionarioModel extends Model
{
    protected $table = 'funcionario';
    protected $primaryKey = 'idFuncionario';
    public $timestamps = false;
    protected $fillable = [  'nombre','documento','correo','telefono','password','estado','idTipoContrato'];
    public const PAGINATION = 10;


}
