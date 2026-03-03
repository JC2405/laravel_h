<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class FuncionarioModel extends Authenticatable implements JWTSubject
{
    protected $table      = 'funcionario';
    protected $primaryKey = 'idFuncionario';
    public    $timestamps = false;

    protected $fillable = [
        'nombre',
        'documento',
        'correo',
        'telefono',
        'password',
        'estado',
        'idTipoContrato',
    ];

    protected $hidden = [
        'password'
    ];

    // 🔐 Hasheo automático de contraseña
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public const PAGINATION = 10;

    // ── JWT obligatorios ──────────────────────────────────────
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        $rol = $this->roles()->first();

        return [
            'guard'  => 'funcionario',
            'rol'    => $rol ? strtolower($rol->nombreRol) : null,
            'nombre' => $this->nombre,
        ];
    }

    // ── Authenticatable helpers ───────────────────────────────
    public function getAuthIdentifierName(): string
    {
        return 'idFuncionario';
    }

    public function getAuthPassword(): string
    {
        return $this->password;
    }

    // ── Relaciones ────────────────────────────────────────────
    public function tipoContrato()
    {
        return $this->belongsTo(
            TipoContratoModel::class,
            'idTipoContrato',
            'idTipoContrato'
        );
    }

    public function bloques()
    {
        return $this->hasMany(
            BloqueHorarioModel::class,
            'idFuncionario',
            'idFuncionario'
        );
    }

    public function areas()
    {
        return $this->belongsToMany(
            AreaModel::class,
            'funcionario_area',
            'idFuncionario',
            'idArea'
        );
    }

    public function roles()
    {
        return $this->belongsToMany(
            \App\Models\RolModel::class,
            'funcionario_rol',
            'idFuncionario',
            'idRol'
        )->withPivot('fechaRegistro');
    }
}   