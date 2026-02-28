<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funcionario_area', function (Blueprint $table) {
            $table->id('idFuncionarioArea');
            $table->unsignedBigInteger('idFuncionario');
            $table->unsignedBigInteger('idArea');

            $table->unique(['idFuncionario', 'idArea'], 'uq_funcionario_area');

            $table->foreign('idFuncionario', 'fk_farea_funcionario')
                  ->references('idFuncionario')->on('funcionario')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->foreign('idArea', 'fk_farea_area')
                  ->references('idArea')->on('area')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funcionario_area');
    }
};
