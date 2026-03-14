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
        Schema::create('asignacion', function (Blueprint $table) {
            $table->unsignedInteger('idAsignacion')->autoIncrement();
            $table->unsignedInteger('idFuncionario');
            $table->unsignedInteger('idAmbiente')->nullable();
            $table->unsignedInteger('idFicha');
            $table->string('modalidad',255);
            $table->string('estado',20)->default('Activo');
 
            $table->foreign('idFuncionario', 'fkAsigFuncionario')
                  ->references('idFuncionario')->on('funcionario')
                  ->onUpdate('cascade');
 
            $table->foreign('idAmbiente', 'fkAsigAmbiente')
                  ->references('idAmbiente')->on('ambiente')
                  ->onUpdate('cascade');
 
            $table->foreign('idFicha', 'fkAsigFicha')
                  ->references('idFicha')->on('ficha')
                  ->onUpdate('cascade');
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asignacion');
    }
};
