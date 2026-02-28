<?php

use App\Http\Controllers\AreaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('listarAreas',[AreaController::class,'index']);
        