<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramaModel extends Model
{
    protected $table = 'programa';
    protected $primaryKey = 'idPrograma';
    public $timestamps = false;
    protected $fillable = ['nombre','codigo','version','estado','idTipoFormacion'];
    public const PAGINATION = 10;


    public function tipoFormacion() {
    return $this->belongsTo(TipoFormacionModel::class, 'idTipoFormacion', 'idTipoFormacion');
    }
    public function fichas() {
        return $this->hasMany(FichaModel::class, 'idPrograma', 'idPrograma');
    }
    public function competencias() {
        return $this->hasMany(CompetenciaModel::class, 'idPrograma', 'idPrograma');
    }
}
