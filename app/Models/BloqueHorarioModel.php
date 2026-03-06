<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BloqueHorarioModel extends Model
{
    protected $table      = 'bloque_horario';
    protected $primaryKey = 'idBloque';
    public    $timestamps = false;

    protected $fillable = [
        'hora_inicio',
        'hora_fin',
        'modalidad',
        'tipoDeFormacion',
        'idAmbiente',
        'idFuncionario',
    ];

    public const PAGINATION = 10;

    public function funcionario() {
        return $this->belongsTo(FuncionarioModel::class, 'idFuncionario', 'idFuncionario');
    }
    public function ambiente() {
        return $this->belongsTo(AmbienteModel::class, 'idAmbiente', 'idAmbiente');
    }
    public function dias() {
        return $this->belongsToMany(DiaModel::class, 'bloque_dia', 'idBloque', 'idDia');
    }
    public function asignaciones() {
        return $this->hasMany(AsignacionModel::class, 'idBloque', 'idBloque');
    }
}