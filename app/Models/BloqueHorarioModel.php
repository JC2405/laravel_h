<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BloqueHorarioModel extends Model
{
    protected $table      = 'bloque';
    protected $primaryKey = 'idBloque';
    public    $timestamps = false;

    protected $fillable = [
        'idAsignacion',
        'fechaInicio',
        'fechaFin',
        'horaInicio',
        'horaFin',
        'estado',
        'observaciones',
        'tipoFormacion',
    ];


    public function asignacion() {
        return $this->belongsTo(AsignacionModel::class, 'idAsignacion', 'idAsignacion');
    }

    public function dias() {
        return $this->belongsToMany(
            DiaModel::class,
            'bloqueDia',  // tabla pivot correcta (mayúscula D)
            'idBloque',   // FK hacia este modelo (bloque)
            'idDia'       // FK hacia DiaModel
        );
    }
}