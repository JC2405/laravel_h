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
        Schema::create('competencia', function (Blueprint $table) {
            $table->unsignedInteger('idCompetencia')->autoIncrement();
            $table->string('nombreCompetencia', 200);
            $table->string('codigo', 40)->unique('uqCompetenciaCodigo');
            $table->string('tipo', 50);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competencia');
    }
};
