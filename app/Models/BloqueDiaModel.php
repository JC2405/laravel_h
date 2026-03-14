<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BloqueDiaModel extends Model
{
    protected $table = 'bloqueDia';
    protected $primaryKey = 'idBloqueDia';
    public $timestamps = false;
    protected $fillable = [ 'idBloque' , 'idDia'];

    
}