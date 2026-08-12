<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Permite múltiples inspecciones por equipo (historial).
     */
    public function up(): void
    {
        if (!Schema::hasTable('equipo_inspecciones')) {
            return;
        }

        $dbName = DB::getDatabaseName();
        $hasUnique = !empty(DB::select(
            "SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1",
            [$dbName, 'equipo_inspecciones', 'equipo_inspecciones_equipo_id_unique']
        ));
        $fkRow = DB::selectOne(
            "SELECT CONSTRAINT_NAME as name
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1",
            [$dbName, 'equipo_inspecciones', 'equipo_id']
        );
        $fkName = $fkRow?->name ?? null;

        Schema::table('equipo_inspecciones', function (Blueprint $table) {
            // Estas operaciones deben ser idempotentes (migrate:fresh en distintas BD).
            // Se aplican solo si existen.
            //
            // Nota: el nombre de FK puede variar entre motores, por eso se detecta fuera del closure.
        });

        if ($fkName) {
            Schema::table('equipo_inspecciones', function (Blueprint $table) use ($fkName) {
                $table->dropForeign($fkName);
            });
        }

        if ($hasUnique) {
            Schema::table('equipo_inspecciones', function (Blueprint $table) {
                $table->dropUnique('equipo_inspecciones_equipo_id_unique');
            });
        }

        // Re-crea FK a equipos (si no existe)
        $fkRowAfter = DB::selectOne(
            "SELECT CONSTRAINT_NAME as name
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1",
            [$dbName, 'equipo_inspecciones', 'equipo_id']
        );
        if (!$fkRowAfter?->name) {
            Schema::table('equipo_inspecciones', function (Blueprint $table) {
                $table->foreign('equipo_id')->references('id')->on('equipos')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('equipo_inspecciones')) {
            return;
        }

        Schema::table('equipo_inspecciones', function (Blueprint $table) {
            $table->dropForeign(['equipo_id']);
            $table->foreignId('equipo_id')->unique()->constrained('equipos')->cascadeOnDelete();
        });
    }
};
