<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sede', function (Blueprint $table) {
            $table->id('idSede');
            $table->string('nombre', 120);
            $table->string('direccion', 255)->nullable();
            $table->string('descripcion', 255)->nullable();
            $table->string('estado', 20);
            $table->unsignedBigInteger('idMunicipio')->nullable();

            $table->foreign('idMunicipio', 'fk_sede_municipio')
                  ->references('idMunicipio')->on('municipio')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sede');
    }
};
