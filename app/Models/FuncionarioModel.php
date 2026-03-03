<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;

class FuncionarioModel extends Model
{
    protected $table = 'funcionario';
    protected $primaryKey = 'idFuncionario';
    public $timestamps = false;
    protected $fillable = [  'nombre','documento','correo','telefono','password','estado','idTipoContrato'];
    public const PAGINATION = 10;

    public function getJWTIdentifier()
    {
        return $this->getKey(); // retorna idFuncionario
    }

    public function getJWTCustomClaims(): array
    {
        // Incluimos el rol directamente en el payload del token
        $rol = $this->roles()->first();
        return [
            'guard' => 'funcionario',
            'rol'   => $rol ? strtolower($rol->nombreRol) : null,
            'nombre'=> $this->nombre,
        ];
    }

    public function tipoContrato() {
    return $this->belongsTo(TipoContratoModel::class, 'idTipoContrato', 'idTipoContrato');
    }
    public function bloques() {
        return $this->hasMany(BloqueHorarioModel::class, 'idFuncionario', 'idFuncionario');
    }
    public function areas() {
        return $this->belongsToMany(AreaModel::class, 'funcionario_area', 'idFuncionario', 'idArea');
    }
    public function roles() {
        return $this->belongsToMany(RolesModels::class, 'funcionario_rol', 'idFuncionario', 'idRol');
    }
}
