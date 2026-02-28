<?php


use App\Http\Controllers\AreaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DiaController;

Route::get('listarArea', [AreaController::class, 'index']);
Route::post('crearArea', [AreaController::class, 'store']);
Route::get('verArea/{areaModel}', [AreaController::class, 'show']);
Route::put('editarArea/{areaModel}', [AreaController::class, 'update']);
Route::delete('eliminarArea/{areaModel}', [AreaController::class, 'destroy']);  
        


Route::get('listarDia', [DiaController::class,'index']);
Route::post('crearDia',[DiaController::class,'store']);
Route::put('editarDia/{idDia}',[DiaController::class,'update']);
Route::delete('eliminarDia/{idDia}',[DiaController::class,'destroy']);