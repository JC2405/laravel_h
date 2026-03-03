<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class RolModel extends Model
{
    protected $table      = 'rol';
    protected $primaryKey = 'idRol';
    public    $timestamps = false;

    protected $fillable = ['nombreRol', 'descripcion'];

    public function funcionarios()
    {
        return $this->belongsToMany(FuncionarioModel::class, 'funcionario_rol', 'idRol', 'idFuncionario')
                    ->withPivot('fechaRegistro');
    }
}