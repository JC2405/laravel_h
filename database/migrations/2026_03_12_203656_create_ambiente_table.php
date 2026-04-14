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
        Schema::create('ambiente', function (Blueprint $table) {
          $table->unsignedInteger('idAmbiente')->autoIncrement();
            $table->string('codigo', 255)->nullable()   ;
            $table->integer('capacidad');
            $table->string('descripcion', 255)->nullable();
            $table->string('bloque', 100)->nullable();
            $table->string('estado',20)->default('Activo');
            $table->string('tipoAmbiente', 100)->nullable();
            $table->unsignedInteger('idSede');
            $table->unsignedInteger('idArea');
 
            $table->foreign('idSede', 'fkAmbienteSede')
                ->references('idSede')->on('sede')
                ->onUpdate('cascade');

            $table->foreign('idArea', 'fkAmbienteArea')
                ->references('idArea')->on('area')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ambiente');
    }
};
