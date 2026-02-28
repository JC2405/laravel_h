<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programa', function (Blueprint $table) {
            $table->id('idPrograma');
            $table->string('nombre', 160);
            $table->string('codigo', 40)->unique('uq_programa_codigo');
            $table->integer('version')->nullable();
            $table->enum('estado', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
            $table->unsignedBigInteger('idTipoFormacion');

            $table->foreign('idTipoFormacion', 'fk_programa_tipoFormacion')
                  ->references('idTipoFormacion')->on('tipo_formacion')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programa');
    }
};
