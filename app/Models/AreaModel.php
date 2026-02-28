<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AreaModel extends Model
{

     protected $table = 'area';
    protected $primaryKey = 'idArea';
    public $timestamps = false;
    protected $fillable = ['nombreArea'];
    public const PAGINATION = 10;
    
    
}
