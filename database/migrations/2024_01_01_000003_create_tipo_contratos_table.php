<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_contrato', function (Blueprint $table) {
            $table->id('idTipoContrato');
            $table->string('nombre', 60)->unique('uq_tipoContrato_nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_contrato');
    }
};
