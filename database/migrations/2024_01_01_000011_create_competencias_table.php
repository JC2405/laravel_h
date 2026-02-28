<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competencia', function (Blueprint $table) {
            $table->id('idCompetencia');
            $table->string('nombre', 200);
            $table->string('codigo', 40)->unique('uq_competencia_codigo');
            $table->string('tipo', 50);
            $table->integer('horas')->nullable();
            $table->string('estado', 20);
            $table->unsignedBigInteger('idPrograma')->nullable();

            $table->foreign('idPrograma', 'fk_competencia_programa')
                  ->references('idPrograma')->on('programa')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competencia');
    }
};
