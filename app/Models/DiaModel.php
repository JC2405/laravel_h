<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiaModel extends Model
{
    protected $table = 'dia';
    protected $primaryKey = 'idDia';
    public $timestamps = false;
    protected $fillable = ['nombre'];
    public const PAGINATION = 10;
}
