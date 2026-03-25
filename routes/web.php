<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;

Route::get('/', function () {
    return view('welcome');

});


Route::get('/test-correo', function () {
    Mail::raw('Correo de prueba desde Laravel', function ($msg) {
        $msg->to('TU_CORREO@gmail.com')
            ->subject('Prueba OK');
    });

    return 'Correo enviado';
});