<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('codigos_reutilizables')) {
            DB::statement('ALTER TABLE codigos_reutilizables MODIFY codigo VARCHAR(80) NOT NULL');
        }

        if (Schema::hasTable('equipos_auditoria_traspasos')) {
            DB::statement('ALTER TABLE equipos_auditoria_traspasos MODIFY codigo_aud VARCHAR(80) NOT NULL');
            DB::statement('ALTER TABLE equipos_auditoria_traspasos MODIFY codigo_anterior VARCHAR(80) NULL');
            if (!Schema::hasColumn('equipos_auditoria_traspasos', 'motivo')) {
                DB::statement('ALTER TABLE equipos_auditoria_traspasos ADD motivo TEXT NULL AFTER codigo_anterior');
            }
        }

        if (Schema::hasTable('material_didactico_traspasos')) {
            DB::statement('ALTER TABLE material_didactico_traspasos MODIFY codigo_md VARCHAR(80) NOT NULL');
            DB::statement('ALTER TABLE material_didactico_traspasos MODIFY codigo_anterior VARCHAR(80) NULL');
            if (!Schema::hasColumn('material_didactico_traspasos', 'motivo')) {
                DB::statement('ALTER TABLE material_didactico_traspasos ADD motivo TEXT NULL AFTER codigo_anterior');
            }
        }

        if (Schema::hasTable('equipos_baja')) {
            DB::statement('ALTER TABLE equipos_baja MODIFY codigo_db VARCHAR(80) NOT NULL');
            DB::statement('ALTER TABLE equipos_baja MODIFY codigo_in VARCHAR(80) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('equipos_auditoria_traspasos') && Schema::hasColumn('equipos_auditoria_traspasos', 'motivo')) {
            DB::statement('ALTER TABLE equipos_auditoria_traspasos DROP COLUMN motivo');
        }
        if (Schema::hasTable('material_didactico_traspasos') && Schema::hasColumn('material_didactico_traspasos', 'motivo')) {
            DB::statement('ALTER TABLE material_didactico_traspasos DROP COLUMN motivo');
        }
    }
};
