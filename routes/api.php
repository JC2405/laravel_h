<?php

use App\Http\Controllers\PersonasController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;

Route::apiResource('posts', PostController::class);
Route::apiResource('personas',PersonasController::class);

