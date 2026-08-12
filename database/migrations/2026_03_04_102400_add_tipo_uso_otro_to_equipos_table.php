<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->string('tipo_uso_otro', 120)->nullable()->after('tipo_uso');
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropColumn('tipo_uso_otro');
        });
    }
};
