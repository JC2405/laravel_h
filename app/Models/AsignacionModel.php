<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AsignacionModel extends Model
{
    protected $table      = 'asignacion';
    protected $primaryKey = 'idAsignacion';
    public    $timestamps = false;

    protected $fillable = [
        'idFuncionario',
        'idAmbiente',
        'idFicha',
        'modalidad',
        'estado',
    ];



    public function funcionario(){
        return $this->belongsTo(FuncionarioModel::class, 'idFuncionario','idFuncionario');
   }

   public function ambiente(){
    return $this->belongsTo(AmbienteModel::class,'idAmbiente', 'idAmbiente');
   }

    public function ficha() {
        return $this->belongsTo(FichaModel::class, 'idFicha', 'idFicha');
    }
    // En AsignacionModel.php
    public function bloque()
    {
        return $this->hasOne(BloqueHorarioModel::class, 'idAsignacion', 'idAsignacion');
    }
}