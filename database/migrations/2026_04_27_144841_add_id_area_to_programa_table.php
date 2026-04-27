<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programa', function (Blueprint $table) {
            $table->unsignedInteger('idArea')->nullable()->after('idTipoFormacion');

            $table->foreign('idArea', 'fkProgramaArea')
                  ->references('idArea')
                  ->on('area')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('programa', function (Blueprint $table) {
            $table->dropForeign(['idArea']);
            $table->dropColumn('idArea');
        });
    }
};