<?php

use App\Http\Controllers\AmbienteController;
use App\Http\Controllers\AprendizController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DiaController;
use App\Http\Controllers\FichaController;
use App\Http\Controllers\FuncionarioController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\MunicipioController;
use App\Http\Controllers\ProgramaController;
use App\Http\Controllers\SedeController;
use App\Http\Controllers\TipoContratoController;
use App\Http\Controllers\TipoFormacionController;

Route::post('login', [AuthController::class, 'login']);

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




Route::get('listarTipoFormacion',[TipoFormacionController::class,'index']);
Route::post('crearTipoFormacion',[TipoFormacionController::class,'store']);
Route::put('editarTipoFormacion/{idTipoFormacion}',[TipoFormacionController::class,'update']);
Route::delete('eliminarTipoFormacion/{idTipoFormacion}',[TipoFormacionController::class,'destroy']);



Route::get('listarPrograma',[ProgramaController::class,'index']);
Route::post('crearPrograma',[ProgramaController::class,'store']);
Route::put('editarPrograma/{idPrograma}',[ProgramaController::class,'update']);
Route::delete('eliminarPrograma/{idPrograma}',[ProgramaController::class,'destroy']);


Route::get('listarFuncionario',[FuncionarioController::class,'index']);
Route::post('crearFuncionario',[FuncionarioController::class,'store']);
Route::put('editarFuncionario/{idFuncionario}',[FuncionarioController::class,'update']);
Route::get('listarFuncionatioXDocumento/{documento}',[FuncionarioController::class,'show']);
Route::delete('eliminarFuncionario/{idFuncionario}',[FuncionarioController::class,'destroy']);


Route::get('listarAmbiente',[AmbienteController::class,'index']);
Route::post('crearAmbiente',[AmbienteController::class,'store']);
Route::put('editarAmbiente/{idAmbiente}',[AmbienteController::class,'update']);
Route::delete('eliminarAmbiente/{idAmbiente}',[AmbienteController::class,'destroy']);


Route::get('listarFicha',[FichaController::class,'index']);
Route::post('crearFicha',[FichaController::class,'store']);
Route::put('editarFicha/{idFicha}',[FichaController::class,'update']);
Route::delete('eliminarFicha/{idFicha}',[FichaController::class,'destroy']);
Route::get('mostratFichaXCodigo/{codigoFicha}',[FichaController::class,'show']);


Route::get('listarAprendiz',[AprendizController::class,'index']);
Route::post('crearAprendiz',[AprendizController::class,'store']);
Route::put('editarAprendiz/{idAprendiz}',[AprendizController::class,'update']);
Route::delete('eliminarAprendiz/{idAprendiz}',[AprendizController::class,'destroy']);
Route::get('mostrarAprendizXdocumento/{documento}',[AprendizController::class,'show']);



Route::post('crearBloque',[HorarioController::class, 'storeBloque']);


Route::post('crearAsignacion',[HorarioController::class, 'storeAsignacion']);
Route::get('horariosPorFicha/{idFicha}',[HorarioController::class, 'horariosPorFicha']);
Route::delete('eliminarAsignacion/{idAsignacion}',[HorarioController::class, 'destroyAsignacion']);
Route::delete('eliminarDiaDeBloque/{idBloque}/{idDia}', [HorarioController::class, 'destroyDiaDeBloque']);