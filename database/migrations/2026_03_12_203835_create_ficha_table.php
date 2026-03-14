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
         Schema::create('ficha', function (Blueprint $table) {
            $table->unsignedInteger('idFicha')->autoIncrement();
            $table->string('codigoFicha', 40)->unique('uqFichaCodigo');
            $table->string('jornada', 30);
            $table->date('fechaInicio')->nullable();
            $table->date('fechaFin')->nullable();
           $table->string('estado',20)->default('Activo');
            $table->string('modalidad', 30);
            $table->unsignedInteger('idPrograma');
 
            $table->foreign('idPrograma', 'fkFichaPrograma')
                  ->references('idPrograma')->on('programa')
                  ->onUpdate('cascade');
         });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ficha');
    }
};
