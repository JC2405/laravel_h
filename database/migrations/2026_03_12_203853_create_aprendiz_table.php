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
        Schema::create('aprendiz', function (Blueprint $table) {
            $table->unsignedInteger('idAprendiz')->autoIncrement();
            $table->string('nombre', 140);
            $table->string('documento', 40)->unique('uqAprendizDocumento');
            $table->string('correo', 160)->unique('uqAprendizCorreo');
            $table->string('telefono', 40)->nullable();
            $table->string('password', 255)->nullable();
            $table->string('estado',20)->default('Activo');
            $table->unsignedInteger('idFicha');
 
            $table->foreign('idFicha', 'fkAprendizFicha')
                  ->references('idFicha')->on('ficha')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aprendiz');
    }
};
