<?php


use App\Http\Controllers\AreaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//api
Route::get('listarArea',[AreaController::class,'index']);
Route::post('crearArea',[AreaController::class,'store']);
Route::update('editarArea',[AreaController::class,'update']);
        


