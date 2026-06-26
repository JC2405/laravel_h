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
        Schema::create('programa', function (Blueprint $table) {
            $table->unsignedInteger('idPrograma')->autoIncrement();
            $table->string('nombre', 160);
            $table->string('codigo', 40);
            $table->integer('version')->nullable();
            $table->string('estado',20)->default('Activo');
            $table->unsignedInteger('idTipoFormacion');
            $table->unique(['codigo', 'version'], 'uqProgramaCodigoVersion');
            $table->foreign('idTipoFormacion', 'fkProgramaTipoFormacion')
                  ->references('idTipoFormacion')->on('tipoFormacion')
                  ->onUpdate('cascade');

         });    
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programa');
    }
};
