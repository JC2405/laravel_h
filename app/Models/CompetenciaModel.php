<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CompetenciaModel extends Model
{
    protected $table      = 'competencia';
    protected $primaryKey = 'idCompetencia';
    public    $timestamps = false;

    protected $fillable = [  'nombre',   'codigo',  'tipo', 'horas'  , 'estado', 'idPrograma', ];

    public const PAGINATION = 10;

    public function programa() {
        return $this->belongsTo(ProgramaModel::class, 'idPrograma', 'idPrograma');
    }
    
    public function resultados() {
    //    return $this->hasMany(ResultadoAprendizajeModel::class, 'idCompetencia', 'idCompetencia');
    }
}