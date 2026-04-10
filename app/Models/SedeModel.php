<?php

namespace App\Models;

    use Illuminate\Database\Eloquent\Model;


    class SedeModel extends Model
    {

        protected $table = 'sede';
        protected $primaryKey = 'idSede';
        public $timestamps =false;
        protected $fillable = [
            'nombre',
            'direccion',
            'descripcion',
            'estado',
            'idMunicipio'];
        

        public function municipio() {
        return $this->belongsTo(MunicipioModel::class, 'idMunicipio', 'idMunicipio');
        }

        public function fichas() {
        return $this->hasMany(FichaModel::class, 'idSede', 'idSede');
        }
    }
