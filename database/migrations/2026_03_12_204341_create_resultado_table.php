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
        Schema::create('resultado', function (Blueprint $table) {
            $table->unsignedInteger('idResultado')->autoIncrement();
            $table->string('nombre', 255);
            $table->string('codigo', 40)->nullable();
            $table->unsignedInteger('idCompetencia');
 
            $table->foreign('idCompetencia', 'fkResultadoCompetencia')
                  ->references('idCompetencia')->on('competencia')
                  ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resultado');
    }
};
