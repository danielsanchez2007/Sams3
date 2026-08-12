<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            if (!Schema::hasColumn('equipos', 'ubicacion_tipo')) {
                $table->string('ubicacion_tipo', 20)->nullable()->after('sede_id');
            }
            if (!Schema::hasColumn('equipos', 'oficina_id')) {
                $table->foreignId('oficina_id')->nullable()->after('bodega_id')->constrained('oficinas')->nullOnDelete();
            }
            if (!Schema::hasColumn('equipos', 'espacio_id')) {
                $table->foreignId('espacio_id')->nullable()->after('oficina_id')->constrained('espacios')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            if (Schema::hasColumn('equipos', 'espacio_id')) {
                $table->dropForeign(['espacio_id']);
                $table->dropColumn('espacio_id');
            }
            if (Schema::hasColumn('equipos', 'oficina_id')) {
                $table->dropForeign(['oficina_id']);
                $table->dropColumn('oficina_id');
            }
            if (Schema::hasColumn('equipos', 'ubicacion_tipo')) {
                $table->dropColumn('ubicacion_tipo');
            }
        });
    }
};
