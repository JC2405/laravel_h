<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CompetenciaModel extends Model
{
    protected $table      = 'competencia';
    protected $primaryKey = 'idCompetencia';
    public    $timestamps = false;

    protected $fillable = [  'nombreCompetencia','codigo','tipo' ];

    public const PAGINATION = 10;

    public function resultados()
    {
        return $this->hasMany(ResultadoModel::class, 'idCompetencia', 'idCompetencia');
    }
    
    
}