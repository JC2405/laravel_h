<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funcionario_rol', function (Blueprint $table) {
            $table->id('idFuncionarioRol');
            $table->unsignedBigInteger('idFuncionario');
            $table->unsignedBigInteger('idRol');
            $table->dateTime('fechaRegistro')->useCurrent();

            $table->unique(['idFuncionario', 'idRol'], 'uq_funcionario_rol');

            $table->foreign('idFuncionario', 'fk_frol_funcionario')
                  ->references('idFuncionario')->on('funcionario')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->foreign('idRol', 'fk_frol_rol')
                  ->references('idRol')->on('rol')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funcionario_rol');
    }
};
