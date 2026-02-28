<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funcionario', function (Blueprint $table) {
            $table->id('idFuncionario');
            $table->string('nombre', 140);
            $table->string('documento', 40)->nullable();
            $table->string('correo', 160)->unique('uq_funcionario_correo');
            $table->string('telefono', 40)->nullable();
            $table->string('password', 255);
            $table->string('estado', 20);
            $table->unsignedBigInteger('idTipoContrato');

            $table->foreign('idTipoContrato', 'fk_funcionario_tipoContrato')
                  ->references('idTipoContrato')->on('tipo_contrato')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funcionario');
    }
};
