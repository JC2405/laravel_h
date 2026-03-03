<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmbienteModel extends Model
{
    protected $table = 'ambiente';
    protected $primaryKey = 'idAmbiente';
    public $timestamps =false;
    protected $fillable = ['codigo','capacidad','numero','descripcion','bloque','estado','tipoAmbiente','idSede','idArea'];
    public const PAGINATION=10;

    
        public function sede()
        {
            return $this->belongsTo(SedeModel::class, 'idSede', 'idSede');
        }

        public function area() {
            return $this->belongsTo(AreaModel::class, 'idArea', 'idArea');
        }
        public function fichas() {
            return $this->hasMany(FichaModel::class, 'idAmbiente', 'idAmbiente');
        }

        public function bloques() {
        return $this->hasMany(BloqueHorarioModel::class, 'idAmbiente', 'idAmbiente');
        }
}
