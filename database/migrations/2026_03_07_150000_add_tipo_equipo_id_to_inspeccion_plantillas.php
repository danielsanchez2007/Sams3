<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inspeccion_plantillas')) {
            return;
        }

        if (!Schema::hasColumn('inspeccion_plantillas', 'tipo_equipo_id')) {
            Schema::table('inspeccion_plantillas', function (Blueprint $table) {
                $table->unsignedBigInteger('tipo_equipo_id')->nullable()->after('id');
                $table->foreign('tipo_equipo_id')->references('id')->on('tipo_equipos')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inspeccion_plantillas') && Schema::hasColumn('inspeccion_plantillas', 'tipo_equipo_id')) {
            Schema::table('inspeccion_plantillas', function (Blueprint $table) {
                $table->dropForeign(['tipo_equipo_id']);
                $table->dropColumn('tipo_equipo_id');
            });
        }
    }
};
