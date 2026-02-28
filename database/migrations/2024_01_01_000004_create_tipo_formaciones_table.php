<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_formacion', function (Blueprint $table) {
            $table->id('idTipoFormacion');
            $table->string('nombre', 80)->unique('uq_tipoFormacion_nombre');
            $table->integer('duracion_meses')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_formacion');
    }
};
