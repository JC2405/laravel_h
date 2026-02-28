<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignacion', function (Blueprint $table) {
            $table->id('idAsignacion');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('estado', 20)->nullable();
            $table->dateTime('creado_en')->useCurrent();
            $table->unsignedBigInteger('idBloque');
            $table->unsignedBigInteger('idFicha');

            $table->index(['fecha_inicio', 'fecha_fin'], 'idx_asig_rango');
            $table->index('idFicha', 'idx_asig_ficha');
            $table->index('idBloque', 'idx_asig_bloque');

            $table->foreign('idBloque', 'fk_asig_bloque')
                  ->references('idBloque')->on('bloque_horario')
                  ->onUpdate('cascade');

            $table->foreign('idFicha', 'fk_asig_ficha')
                  ->references('idFicha')->on('ficha')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignacion');
    }
};
