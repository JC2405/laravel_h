<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoFormacionModel extends Model
{
    protected $table = 'tipoFormacion';
    protected $primaryKey = 'idTipoFormacion';
    public $timestamps =false;
    protected $fillable = ['nombreTipoFormacion','duracionMeses'];
    

    
    public function programas() {
        return $this->hasMany(ProgramaModel::class, 'idTipoFormacion', 'idTipoFormacion');
    }

    public function competencias()
    {
        return $this->hasMany(CompetenciaModel::class, 'idTipoFormacion', 'idTipoFormacion');
    }
}
