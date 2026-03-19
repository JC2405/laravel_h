<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CompetenciaModel extends Model
{
    protected $table      = 'competencia';
    protected $primaryKey = 'idCompetencia';
    public    $timestamps = false;

    protected $fillable = [  'nombreCompetencia','codigo','tipo' , 'idTipoFormacion' ];

    public const PAGINATION = 10;

   
    
    public function tipoFormacion()
    {
        return $this->belongsTo(TipoFormacionModel::class, 'idTipoFormacion', 'idTipoFormacion');
    }

    
}