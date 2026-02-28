<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ficha', function (Blueprint $table) {
            $table->id('idFicha');
            $table->string('codigoFicha', 40)->unique('uq_ficha_codigo');
            $table->string('jornada', 30);
            $table->date('fechaInicio')->nullable();
            $table->date('fechaFin')->nullable();
            $table->string('estado', 20);
            $table->string('modalidad', 30);
            $table->unsignedBigInteger('idPrograma');
            $table->unsignedBigInteger('idAmbiente')->nullable();

            $table->foreign('idPrograma', 'fk_ficha_programa')
                  ->references('idPrograma')->on('programa')
                  ->onUpdate('cascade');

            $table->foreign('idAmbiente', 'fk_ficha_ambiente')
                  ->references('idAmbiente')->on('ambiente')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ficha');
    }
};
