<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class ResultadoModel extends Model
{

    protected $table = 'resultado';
    protected $primaryKey = 'idResultado';
    public $timestamps = false ;
    protected $fillable = [ 'nombre' , 'codigo' , 'idCompetencia'];


    public function competencia()
    {
        return $this->belongsTo(CompetenciaModel::class, 'idCompetencia', 'idCompetencia');
    }
}