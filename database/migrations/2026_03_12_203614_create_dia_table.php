<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dia', function (Blueprint $table) {
            $table->unsignedInteger('idDia')->autoIncrement();
            $table->string('nombreDia', 20)->unique('uqDiaNombre');
        });

        DB::table('dia')->insert([
            ['nombreDia' => 'Lunes'],
            ['nombreDia' => 'Martes'],
            ['nombreDia' => 'Miercoles'],
            ['nombreDia' => 'Jueves'],
            ['nombreDia' => 'Viernes'],
            ['nombreDia' => 'Sabado'],
            ['nombreDia' => 'Domingo']
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('dia');
    }
};