<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rol', function (Blueprint $table) {
            $table->unsignedInteger('idRol')->autoIncrement();
            $table->string('rol', 60);
        });

         DB::table('rol')->insert([
            ['rol' => 'Coordinador'],
            ['rol' => 'Instructor'],
            ['rol' => 'Aprendiz']
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rol');
    }
};
