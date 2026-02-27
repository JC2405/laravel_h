<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Personas extends Model
{
        protected $fillable = ['nombre' , 'apellido' , 'password'];


        public const PAGINATE = 100 ; 
}
