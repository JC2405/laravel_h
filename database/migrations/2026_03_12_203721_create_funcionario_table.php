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
        Schema::create('funcionario', function (Blueprint $table) {
            $table->unsignedInteger('idFuncionario')->autoIncrement();
            $table->string('nombre', 140);
            $table->string('apellido',140);
            $table->string('documento', 40)->nullable();
            $table->string('correo', 160)->unique('uqFuncionarioCorreo');
            $table->string('telefono', 40)->nullable();
            $table->string('password', 255);
            $table->string('estado',20)->default('Activo');
            $table->unsignedInteger('idTipoContrato');
 
            $table->foreign('idTipoContrato', 'fkFuncionarioTipoContrato')
                  ->references('idTipoContrato')->on('tipoContrato')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funcionario');
    }
};
