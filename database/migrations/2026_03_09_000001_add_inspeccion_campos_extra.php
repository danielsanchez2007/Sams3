<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('equipo_inspecciones')) {
            return;
        }

        Schema::table('equipo_inspecciones', function (Blueprint $table) {
            if (!Schema::hasColumn('equipo_inspecciones', 'inspector_user_id')) {
                $table->unsignedBigInteger('inspector_user_id')->nullable()->after('user_id');
                if (Schema::hasTable('users')) {
                    $table->foreign('inspector_user_id')->references('id')->on('users')->nullOnDelete();
                }
            }
            if (!Schema::hasColumn('equipo_inspecciones', 'selected_user_ids')) {
                $table->json('selected_user_ids')->nullable()->after('inspector_user_id');
            }
            if (!Schema::hasColumn('equipo_inspecciones', 'dado_de_baja')) {
                $table->boolean('dado_de_baja')->default(false)->after('selected_user_ids');
            }
            if (!Schema::hasColumn('equipo_inspecciones', 'equipo_baja_id')) {
                $table->unsignedBigInteger('equipo_baja_id')->nullable()->after('dado_de_baja');
            }
        });

        if (Schema::hasTable('equipos_baja') && Schema::hasColumn('equipo_inspecciones', 'equipo_baja_id')) {
            Schema::table('equipos_baja', function (Blueprint $table) {
                if (!Schema::hasColumn('equipos_baja', 'equipo_inspeccion_id')) {
                    $table->unsignedBigInteger('equipo_inspeccion_id')->nullable()->after('equipo_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('equipo_inspecciones')) {
            Schema::table('equipo_inspecciones', function (Blueprint $table) {
                $columns = ['inspector_user_id', 'selected_user_ids', 'dado_de_baja', 'equipo_baja_id'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('equipo_inspecciones', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
        if (Schema::hasTable('equipos_baja') && Schema::hasColumn('equipos_baja', 'equipo_inspeccion_id')) {
            Schema::table('equipos_baja', function (Blueprint $table) {
                $table->dropColumn('equipo_inspeccion_id');
            });
        }
    }
};
