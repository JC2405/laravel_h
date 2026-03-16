<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiaModel extends Model
{
    protected $table      = 'dia';
    protected $primaryKey = 'idDia';
    public    $timestamps = false;
    protected $fillable   = ['nombreDia'];
    // Exporta el accessor 'nombre' en el JSON automáticamente
    protected $appends    = ['nombre'];
    public const PAGINATION = 10;

    // Permite usar dia.nombre en JS aunque la columna sea nombreDia
    public function getNombreAttribute(): string
    {
        return $this->nombreDia ?? '';
    }
}

