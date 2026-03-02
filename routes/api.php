<?php


use App\Http\Controllers\AreaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DiaController;
use App\Http\Controllers\MunicipioController;
use App\Http\Controllers\MunicipiosController;
use App\Http\Controllers\SedeController;
use App\Http\Controllers\TipoContratoController;
use App\Models\MunicipioModel;
use Hamcrest\Core\Set;

Route::get('listarArea', [AreaController::class, 'index']);
Route::post('crearArea', [AreaController::class, 'store']);
Route::get('verArea/{areaModel}', [AreaController::class, 'show']);
Route::put('editarArea/{areaModel}', [AreaController::class, 'update']);
Route::delete('eliminarArea/{areaModel}', [AreaController::class, 'destroy']);  
        


Route::get('listarDia', [DiaController::class,'index']);
Route::post('crearDia',[DiaController::class,'store']);
Route::put('editarDia/{idDia}',[DiaController::class,'update']);
Route::delete('eliminarDia/{idDia}',[DiaController::class,'destroy']);



Route::get('listarTipoContrato',[TipoContratoController::class,'index']);
Route::post('crearTipoContrato',[TipoContratoController::class,'store']);
Route::put('editarTipoContrato/{idTipoContrato}',[TipoContratoController::class,'update']);
Route::delete('eliminarTipoContrato/{idTipoContrato}',[TipoContratoController::class,'destroy']);



Route::get('listarMunicipio',[MunicipioController::class,'index']);
Route::post('crearMunicipio',[MunicipioController::class,'store']);
Route::delete('eliminarMunicipio/{idMunicipio}',[MunicipioController::class,'destroy']);




Route::get('listarSedes',[SedeController::class,'index']);
Route::post('crearSede',[SedeController::class,'store']);
Route::put('editarSede/{idSede}',[SedeController::class,'update']);
Route::delete('eliminarSede/{idSede}',[SedeController::class,'destroy']);