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
        Schema::create('sede', function (Blueprint $table) {
             $table->unsignedInteger('idSede')->autoIncrement();
            $table->string('nombre', 120);
            $table->string('direccion', 255)->nullable();
            $table->string('descripcion', 255)->nullable();
            $table->string('estado',20)->default('Activo');
            $table->unsignedInteger('idMunicipio')->nullable();
 
            $table->foreign('idMunicipio', 'fkSedeMunicipio')
                ->references('idMunicipio')
                ->on('municipio')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sede');
    }
};
