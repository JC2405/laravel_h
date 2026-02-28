<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ambiente', function (Blueprint $table) {
            $table->id('idAmbiente');
            $table->string('codigo', 255)->nullable()->unique('uq_ambiente_codigo');
            $table->integer('capacidad');
            $table->string('numero', 40);
            $table->string('descripcion', 255)->nullable();
            $table->string('bloque', 100)->nullable();
            $table->string('estado', 20);
            $table->string('tipoAmbiente', 100)->nullable();
            $table->unsignedBigInteger('idSede');
            $table->unsignedBigInteger('idArea');

            $table->foreign('idSede', 'fk_ambiente_sede')
                  ->references('idSede')->on('sede')
                  ->onUpdate('cascade');

            $table->foreign('idArea', 'fk_ambiente_area')
                  ->references('idArea')->on('area')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ambiente');
    }
};
