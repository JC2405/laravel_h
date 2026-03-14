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
        Schema::create('funcionarioArea', function (Blueprint $table) {
            $table->unsignedInteger('idFuncionarioArea')->autoIncrement();
            $table->unsignedInteger('idFuncionario');
            $table->unsignedInteger('idArea');
 
            $table->unique(['idFuncionario', 'idArea'], 'uqFuncionarioArea');
 
            $table->foreign('idFuncionario', 'fkFareaFuncionario')
                  ->references('idFuncionario')->on('funcionario')
                  ->onDelete('cascade')->onUpdate('cascade');
 
            $table->foreign('idArea', 'fkFareaArea')
                  ->references('idArea')->on('area')
                  ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funcionarioArea');
    }
};
