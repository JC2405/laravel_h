<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bloque_horario', function (Blueprint $table) {
            $table->id('idBloque');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('modalidad', 30);
            $table->unsignedBigInteger('idAmbiente')->nullable();
            $table->unsignedBigInteger('idFuncionario');

            $table->foreign('idAmbiente', 'fk_bloque_ambiente')
                  ->references('idAmbiente')->on('ambiente')
                  ->onUpdate('cascade');

            $table->foreign('idFuncionario', 'fk_bloque_funcionario')
                  ->references('idFuncionario')->on('funcionario')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bloque_horario');
    }
};
