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
        Schema::create('funcionarioRol', function (Blueprint $table) {
                 $table->unsignedInteger('idFuncionarioRol')->autoIncrement();
            $table->unsignedInteger('idFuncionario');
            $table->unsignedInteger('idRol');
 
            $table->unique(['idFuncionario', 'idRol'], 'uqFuncionarioRol');
 
            $table->foreign('idFuncionario', 'fkFrolFuncionario')
                  ->references('idFuncionario')->on('funcionario')
                  ->onDelete('cascade')->onUpdate('cascade');
 
            $table->foreign('idRol', 'fkFrolRol')
                  ->references('idRol')->on('rol')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funcionarioRol');
    }
};
