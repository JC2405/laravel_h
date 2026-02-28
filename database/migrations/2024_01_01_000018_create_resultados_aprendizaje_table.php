<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultado_aprendizaje', function (Blueprint $table) {
            $table->id('idResultado');
            $table->string('nombre', 255);
            $table->string('codigo', 40)->nullable();
            $table->unsignedBigInteger('idCompetencia');

            $table->foreign('idCompetencia', 'fk_resultado_competencia')
                  ->references('idCompetencia')->on('competencia')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultado_aprendizaje');
    }
};
