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
        // Aseguramos que los roles estén cargados
        if (!$this->relationLoaded('roles')) {
            $this->load('roles');
        }

        $rol = $this->roles->first();

        // nombreRol viene de la tabla 'rol' — lo normalizamos a minúsculas
        // para que el frontend siempre reciba 'instructor' o 'coordinador'
        $nombreRol = $rol ? strtolower(trim($rol->nombreRol)) : null;

        return [
            'guard'  => 'funcionario',
            'rol'    => $nombreRol,
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
            'funcionarioArea',
            'idFuncionario',
            'idArea'
        );
    }

    public function roles()
    {
        return $this->belongsToMany(
            \App\Models\RolModel::class,
            'funcionarioRol',
            'idFuncionario',
            'idRol'
        );
    }
}