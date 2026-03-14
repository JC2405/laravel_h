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
        Schema::create('tipoFormacion', function (Blueprint $table) {
            $table->unsignedInteger('idTipoFormacion')->autoIncrement();
            $table->string('nombreTipoFormacion', 60)->unique('uqTipoFormacionNombre');
            $table->integer('duracionMeses');
        }); 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipoFormacion');
    }
};
