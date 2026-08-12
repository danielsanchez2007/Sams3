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
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('prefijo', 10)->nullable()->after('nombre')->comment('Iniciales para códigos: APW, BVC, etc.');
            $table->string('color_primario', 20)->nullable()->after('prefijo')->comment('Color hex para tema de la empresa');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['prefijo', 'color_primario']);
        });
    }
};
