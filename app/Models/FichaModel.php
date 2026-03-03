<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FichaModel extends Model
{
    protected $table = 'ficha';
    protected $primaryKey = 'idFicha';
    public $timestamps = false;
    protected $fillable = ['codigoFicha','jornada','fechaInicio','fechaFin','estado','modalidad','idPrograma','idAmbiente'];
    public const PAGINATION = 10;

    public function programa() {
    return $this->belongsTo(ProgramaModel::class, 'idPrograma', 'idPrograma');
    }
    public function ambiente() {
        return $this->belongsTo(AmbienteModel::class, 'idAmbiente', 'idAmbiente');
    }
    public function aprendices() {
        return $this->hasMany(AprendizModel::class, 'idFicha', 'idFicha');
    }
    public function asignaciones() {
        return $this->hasMany(AsignacionModel::class, 'idFicha', 'idFicha');
    }
}
