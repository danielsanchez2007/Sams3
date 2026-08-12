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
        Schema::table('tipo_equipos', function (Blueprint $table) {
            $table->string('alias', 80)->nullable()->after('nombre');
        });

        Schema::table('clase_equipos', function (Blueprint $table) {
            $table->string('alias', 80)->nullable()->after('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tipo_equipos', function (Blueprint $table) {
            $table->dropColumn('alias');
        });

        Schema::table('clase_equipos', function (Blueprint $table) {
            $table->dropColumn('alias');
        });
    }
};
