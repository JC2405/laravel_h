<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bloque_dia', function (Blueprint $table) {
            $table->id('idBloqueDia');
            $table->unsignedBigInteger('idBloque');
            $table->unsignedBigInteger('idDia');

            $table->unique(['idBloque', 'idDia'], 'uq_bloque_dia');

            $table->foreign('idBloque', 'fk_bd_bloque')
                  ->references('idBloque')->on('bloque_horario')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->foreign('idDia', 'fk_bd_dia')
                  ->references('idDia')->on('dia')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bloque_dia');
    }
};
