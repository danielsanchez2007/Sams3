<?php

use App\Models\Empresa;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cargos') && !Schema::hasColumn('cargos', 'empresa_id')) {
            Schema::table('cargos', function (Blueprint $table) {
                $table->foreignId('empresa_id')->nullable()->after('id')->constrained('empresas')->nullOnDelete();
                $table->index(['empresa_id', 'activo']);
            });
        }

        if (Schema::hasTable('grupos') && !Schema::hasColumn('grupos', 'empresa_id')) {
            Schema::table('grupos', function (Blueprint $table) {
                $table->foreignId('empresa_id')->nullable()->after('id')->constrained('empresas')->nullOnDelete();
                $table->index(['empresa_id', 'activo']);
            });

            // La tabla tiene unique(name). Para permitir nombres repetidos por empresa,
            // quitamos ese unique y lo reemplazamos por unique(empresa_id, name).
            // Si ya no existe, no falla.
            try {
                Schema::table('grupos', function (Blueprint $table) {
                    $table->dropUnique(['name']);
                });
            } catch (\Throwable $e) {
                // ignore
            }
            try {
                Schema::table('grupos', function (Blueprint $table) {
                    $table->unique(['empresa_id', 'name']);
                });
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // Para no dejar el sistema “sin cargos/grupos” tras migrar,
        // asignamos los existentes a la primera empresa (si existe).
        $empresaId = Empresa::query()->orderBy('id')->value('id');
        if ($empresaId) {
            if (Schema::hasTable('cargos') && Schema::hasColumn('cargos', 'empresa_id')) {
                DB::table('cargos')->whereNull('empresa_id')->update(['empresa_id' => $empresaId]);
            }
            if (Schema::hasTable('grupos') && Schema::hasColumn('grupos', 'empresa_id')) {
                DB::table('grupos')->whereNull('empresa_id')->update(['empresa_id' => $empresaId]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('grupos') && Schema::hasColumn('grupos', 'empresa_id')) {
            // Intentar revertir índices/unicidad de forma segura.
            try {
                Schema::table('grupos', function (Blueprint $table) {
                    $table->dropUnique(['empresa_id', 'name']);
                });
            } catch (\Throwable $e) {
                // ignore
            }
            try {
                Schema::table('grupos', function (Blueprint $table) {
                    $table->unique(['name']);
                });
            } catch (\Throwable $e) {
                // ignore
            }

            Schema::table('grupos', function (Blueprint $table) {
                $table->dropForeign(['empresa_id']);
                $table->dropColumn('empresa_id');
            });
        }

        if (Schema::hasTable('cargos') && Schema::hasColumn('cargos', 'empresa_id')) {
            Schema::table('cargos', function (Blueprint $table) {
                $table->dropForeign(['empresa_id']);
                $table->dropColumn('empresa_id');
            });
        }
    }
};

