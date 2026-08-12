<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('equipos', 'codigo')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `equipos` MODIFY `codigo` VARCHAR(100) NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE equipos ALTER COLUMN codigo TYPE VARCHAR(100)');
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('equipos', 'codigo')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `equipos` MODIFY `codigo` VARCHAR(20) NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE equipos ALTER COLUMN codigo TYPE VARCHAR(20)');
        }
    }
};
