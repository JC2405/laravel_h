<?php


use App\Http\Controllers\AreaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DiaController;
use App\Http\Controllers\TipoContratoController;

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

