<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoContratoModel extends Model
{
    protected $table = 'tipo_contrato';
    protected $primaryKey = 'idTipoContrato';
    public $timestamps = false;
    protected $fillable = ['nombre'];
    public const PAGINATION = 10; 

    public function funcionarios() {
        return $this->hasMany(FuncionarioModel::class, 'idTipoContrato', 'idTipoContrato');
    }
}
