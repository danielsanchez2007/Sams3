<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipo_kit_items', function (Blueprint $table) {
            $table->string('nombre', 200)->nullable()->after('equipo_id');
        });
    }

    public function down(): void
    {
        Schema::table('equipo_kit_items', function (Blueprint $table) {
            $table->dropColumn('nombre');
        });
    }
};
