<?php

namespace App\Services\Ficha;

use App\Models\FichaModel;

class FichaService
{
    public function getAll()
    {
        return FichaModel::with([
            'programa.tipoFormacion',
            'programa.area',
            'asignaciones.ambiente.sede.municipio', 
            'sede.municipio',
        ])  ->orderBy('fechaInicio','DESC') 
            ->get();
    }

    public function create(array $data): FichaModel
    {
        if (FichaModel::where('codigoFicha', $data['codigoFicha'])->exists()) {
            throw new \Exception('El código de ficha "' . $data['codigoFicha'] . '" ya está registrado.');
        }
        return FichaModel::create($data);
    }

    public function buscarFichasPorFechaFin (string $fechaFin)
    {
        return FichaModel::with([
            'programa.tipoFormacion',
            'programa.area',
            'asignaciones.ambiente.sede.municipio', 
            'sede.municipio',
        ]) -> where('fechaFin',"<=" ,$fechaFin)
        ->get();
    }


    public function cambiarEstadoMasivamente(array $idFichas, string $estado): int
    {
        return FichaModel::whereIn('idFicha', $idFichas)
            ->update([
                'estado' => $estado
            ]);
    }

    // Alias de compatibilidad
    public function desactivarMasivamente(array $idFichas): int
    {
        return $this->cambiarEstadoMasivamente($idFichas, 'Inactivo');
    }

   public function update(FichaModel $fichaModel, $data): FichaModel
    {
        if (FichaModel::where('codigoFicha', $data['codigoFicha'])
            ->where('idFicha', '!=', $fichaModel->idFicha)
            ->exists()) {
            throw new \Exception('El código de ficha "' . $data['codigoFicha'] . '" ya está en uso por otra ficha.');
        }
        $fichaModel->update($data);
        return $fichaModel->fresh();
    }

    public function delete(FichaModel $fichaModel): void
    {
        $fichaModel->delete();
    }

    public function findById(int $idFicha): ?object
    {
        return FichaModel::find($idFicha);
    }


    public function show($codigoFicha)
    {
        return FichaModel::with([
            'programa.tipoFormacion',
            'programa.area',
            'sede.municipio',
        ])
        ->where('codigoFicha', $codigoFicha)
        ->firstOrFail();
    }

    public function countActivas(): int
    {
        $count = FichaModel::where('estado', '!=', 'Inactivo')->count();
        return $count > 0 ? $count : FichaModel::count();
    }

    /**
     * Retorna los programas distintos que tienen fichas en el municipio indicado.
     */
    public function obtenerProgramasPorMunicipio(int $idMunicipio): \Illuminate\Support\Collection
    {
        return FichaModel::whereHas('sede', function ($query) use ($idMunicipio) {
                $query->where('idMunicipio', $idMunicipio);
            })
            ->with('programa')
            ->get()
            ->pluck('programa')
            ->filter()
            ->unique('idPrograma')
            ->values();
    }

   
    /**
     * Cuenta fichas agrupadas por programa en un municipio, pasando por la sede.
     */
    public function countFichasPorProgramaYMunicipio($idMunicipio)
    {
        return FichaModel::whereHas('sede', function($query) use ($idMunicipio) {
                $query->where('idMunicipio', $idMunicipio);
            })
            ->with('programa')
            ->select('idPrograma')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('idPrograma')
            ->get();
    }


    public function countFichasPorProgramaYSede($idSede)
    {
        return FichaModel::whereHas('sede',function($query) use ($idSede) {
            $query->where('idSede',$idSede);
        })
         ->with('programa')
            ->select('idPrograma')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('idPrograma')
            ->get();
    }


    //obtener los programas por sede 
    public function obtenerProgramasPorSede(int $idSede): \Illuminate\Support\Collection
    {
    return FichaModel::whereHas('sede', function ($query) use ($idSede) {
            $query->where('idSede', $idSede);
        })
        ->with('programa')
        ->get()
        ->pluck('programa')
        ->filter()
        ->unique('idPrograma')
        ->values();
    }

    public function obtenerFichasPorSede(int $idSede)
    {
        return FichaModel::with([
                'programa.tipoFormacion',
                'programa.area',
                'sede.municipio',
            ])
            ->where('idSede', $idSede)
            ->where('estado', 'Activo')
            ->orderBy('codigoFicha')
            ->get();
    }


    public function obtenerFichasPorProgramaSede(int $idPrograma, int $idSede)
    {
         return FichaModel::with([
            'programa.tipoFormacion',
            'programa.area',
            'sede.municipio',
            'asignaciones.bloque'
        ])
        ->where('idPrograma', $idPrograma)
        ->where('idSede', $idSede)
        ->where('estado', 'Activo')
        ->get();
    }

    /**
     * Retorna las fichas activas que corresponden al programa y municipio dados.
     */
    public function obtenerFichasPorProgramaMunicipio(int $idPrograma, int $idMunicipio)
    {
        return FichaModel::with([
                'programa.tipoFormacion',
                'programa.area',
                'sede.municipio',
                'asignaciones.bloque'
            ])
            ->where('idPrograma', $idPrograma)
            ->whereHas('sede', function ($query) use ($idMunicipio) {
                $query->where('idMunicipio', $idMunicipio);
            })
            ->where('estado', 'Activo')
            ->get();
    }

    public function verificarEstadoFicha(int $idFicha): array
    {
        $ficha = FichaModel::find($idFicha);

        if (!$ficha) {
            return ['ok' => false, 'mensaje' => 'Ficha no encontrada'];
        }

        if ($ficha->estado !== 'Inactivo') {
            return [
                'ok' => false,
                'mensaje' => 'La ficha debe estar inactiva para eliminar el horario'
            ];
        }

        return ['ok' => true];
    }
}