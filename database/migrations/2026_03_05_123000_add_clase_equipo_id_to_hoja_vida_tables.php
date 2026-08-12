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
        Schema::table('hoja_vida_plantillas', function (Blueprint $table) {
            if (!Schema::hasColumn('hoja_vida_plantillas', 'clase_equipo_id')) {
                $table->foreignId('clase_equipo_id')->nullable()->after('tipo_equipo_id')->constrained('clase_equipos')->nullOnDelete();
            }
        });

        Schema::table('hoja_vida_documentos', function (Blueprint $table) {
            if (!Schema::hasColumn('hoja_vida_documentos', 'clase_equipo_id')) {
                $table->foreignId('clase_equipo_id')->nullable()->after('tipo_equipo_id')->constrained('clase_equipos')->nullOnDelete();
            }
        });

        Schema::table('hoja_vida_plantillas', function (Blueprint $table) {
            $table->dropForeign(['tipo_equipo_id']);
            $table->dropUnique(['tipo_equipo_id']);
            $table->index('tipo_equipo_id');
            $table->foreign('tipo_equipo_id')->references('id')->on('tipo_equipos')->cascadeOnDelete();
            $table->unique('clase_equipo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hoja_vida_plantillas', function (Blueprint $table) {
            $table->dropUnique(['clase_equipo_id']);
            $table->dropForeign(['tipo_equipo_id']);
            $table->dropIndex(['tipo_equipo_id']);
            $table->unique('tipo_equipo_id');
            $table->foreign('tipo_equipo_id')->references('id')->on('tipo_equipos')->cascadeOnDelete();
        });

        Schema::table('hoja_vida_documentos', function (Blueprint $table) {
            if (Schema::hasColumn('hoja_vida_documentos', 'clase_equipo_id')) {
                $table->dropConstrainedForeignId('clase_equipo_id');
            }
        });

        Schema::table('hoja_vida_plantillas', function (Blueprint $table) {
            if (Schema::hasColumn('hoja_vida_plantillas', 'clase_equipo_id')) {
                $table->dropConstrainedForeignId('clase_equipo_id');
            }
        });
    }
};
