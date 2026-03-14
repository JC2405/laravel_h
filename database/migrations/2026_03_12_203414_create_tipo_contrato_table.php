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
        Schema::create('tipoContrato', function (Blueprint $table) {
            $table->unsignedInteger('idTipoContrato')->autoIncrement();
            $table->string('nombreTipoContrato', 60)->unique('uqTipoContratoNombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipoContrato');
    }
};
