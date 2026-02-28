<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('area', function (Blueprint $table) {
            $table->id('idArea');
            $table->string('nombreArea', 100)->unique('uq_area_nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area');
    }
};
