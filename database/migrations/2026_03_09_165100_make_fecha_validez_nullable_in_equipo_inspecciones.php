<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('equipo_inspecciones')) {
            return;
        }

        if (Schema::hasColumn('equipo_inspecciones', 'fecha_validez')) {
            DB::statement('ALTER TABLE equipo_inspecciones MODIFY fecha_validez DATE NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('equipo_inspecciones') || !Schema::hasColumn('equipo_inspecciones', 'fecha_validez')) {
            return;
        }
        DB::statement('ALTER TABLE equipo_inspecciones MODIFY fecha_validez DATE NOT NULL');
    }
};
