<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AreaModel extends Model
{
    protected $fillable = ['nombreArea'];

    public const PAGINATION= 10;
}
