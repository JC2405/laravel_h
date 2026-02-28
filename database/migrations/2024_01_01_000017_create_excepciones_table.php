<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('excepcion', function (Blueprint $table) {
            $table->id('idExcepcion');
            $table->date('fecha_excepcion');
            $table->string('tipo', 50);
            $table->string('descripcion', 255)->nullable();
            $table->time('hora_inicio_override')->nullable();
            $table->time('hora_fin_override')->nullable();
            $table->dateTime('creado_en')->useCurrent();
            $table->unsignedBigInteger('idAsignacion');
            $table->unsignedBigInteger('id_funcionario_reemplazo')->nullable();
            $table->unsignedBigInteger('id_ambiente_override')->nullable();

            $table->index('fecha_excepcion', 'idx_exc_fecha');
            $table->index('idAsignacion', 'idx_exc_asignacion');

            $table->foreign('idAsignacion', 'fk_exc_asignacion')
                  ->references('idAsignacion')->on('asignacion')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->foreign('id_funcionario_reemplazo', 'fk_exc_reemplazo')
                  ->references('idFuncionario')->on('funcionario')
                  ->onUpdate('cascade');

            $table->foreign('id_ambiente_override', 'fk_exc_ambiente')
                  ->references('idAmbiente')->on('ambiente')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('excepcion');
    }
};
