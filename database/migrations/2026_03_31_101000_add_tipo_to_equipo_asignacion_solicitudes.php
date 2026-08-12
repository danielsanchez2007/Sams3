<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipo_asignacion_solicitudes', function (Blueprint $table) {
            $table->string('tipo', 20)->default('entrega')->after('to_user_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('equipo_asignacion_solicitudes', function (Blueprint $table) {
            $table->dropIndex(['tipo']);
            $table->dropColumn('tipo');
        });
    }
};

