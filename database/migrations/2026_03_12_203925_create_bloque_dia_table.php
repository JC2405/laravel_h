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
        Schema::create('bloqueDia', function (Blueprint $table) {
           $table->unsignedInteger('idBloqueDia')->autoIncrement();
            $table->unsignedInteger('idBloque');
            $table->unsignedInteger('idDia');
 
 
            $table->foreign('idBloque', 'fkBdBloque')
                  ->references('idBloque')->on('bloque')
                  ->onDelete('cascade')->onUpdate('cascade');
 
            $table->foreign('idDia', 'fkBdDia')
                  ->references('idDia')->on('dia')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bloqueDia');
    }
};
