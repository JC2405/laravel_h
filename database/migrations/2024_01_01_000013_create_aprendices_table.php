<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aprendiz', function (Blueprint $table) {
            $table->id('idAprendiz');
            $table->string('nombre', 140);
            $table->string('documento', 40)->unique('uq_aprendiz_documento');
            $table->string('correo', 160)->unique('uq_aprendiz_correo');
            $table->string('telefono', 40)->nullable();
            $table->string('password', 255)->nullable();
            $table->string('estado', 20);
            $table->unsignedBigInteger('idFicha');

            $table->foreign('idFicha', 'fk_aprendiz_ficha')
                  ->references('idFicha')->on('ficha')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aprendiz');
    }
};
