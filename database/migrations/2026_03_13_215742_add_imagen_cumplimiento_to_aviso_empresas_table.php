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
        Schema::table('aviso_empresas', function (Blueprint $table) {
            $table->string('imagen_cumplimiento_path', 500)->nullable()->after('mensaje');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aviso_empresas', function (Blueprint $table) {
            $table->dropColumn('imagen_cumplimiento_path');
        });
    }
};
