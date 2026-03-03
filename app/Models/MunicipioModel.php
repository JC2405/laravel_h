<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MunicipioModel extends Model
{
    protected $table = 'municipio';
    protected $primaryKey = 'idMunicipio';
    public $timestamps =false;
    protected $fillable = ['nombreMunicipio'];
    public const PAGINATION = 10;

     public function sedes() {
        return $this->hasMany(SedeModel::class, 'idMunicipio', 'idMunicipio');
    }
}
