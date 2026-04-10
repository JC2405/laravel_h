<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiaModel extends Model
{
    protected $table      = 'dia';
    protected $primaryKey = 'idDia';
    public    $timestamps = false;
    protected $fillable   = ['nombreDia'];
    protected $appends    = ['nombre'];

    // Permite usar dia.nombre en JS aunque la columna sea nombreDia
    public function getNombreAttribute(): string
    {
        return $this->nombreDia ?? '';
    }
}

