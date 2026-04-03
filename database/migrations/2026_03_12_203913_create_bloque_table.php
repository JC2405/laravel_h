<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bloque', function (Blueprint $table) {
           $table->unsignedInteger('idBloque')->autoIncrement();
            $table->unsignedInteger('idAsignacion');
            $table->date('fechaInicio');
            $table->date('fechaFin');
            $table->time('horaInicio');
            $table->time('horaFin');
            $table->string('estado',20)->default('Activo');
            $table->string('observaciones', 255)->nullable();

 
            $table->foreign('idAsignacion', 'fkBloqueAsignacion')
                  ->references('idAsignacion')->on('asignacion')
                  ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bloque');
    }
};
