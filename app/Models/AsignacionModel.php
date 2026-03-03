<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AsignacionModel extends Model
{
    protected $table      = 'asignacion';
    protected $primaryKey = 'idAsignacion';
    public    $timestamps = false;

    protected $fillable = [
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'idBloque',
        'idFicha',
    ];

    public const PAGINATION = 10;

    public function bloque() {
        return $this->belongsTo(BloqueHorarioModel::class, 'idBloque', 'idBloque');
    }
    public function ficha() {
        return $this->belongsTo(FichaModel::class, 'idFicha', 'idFicha');
    }
}