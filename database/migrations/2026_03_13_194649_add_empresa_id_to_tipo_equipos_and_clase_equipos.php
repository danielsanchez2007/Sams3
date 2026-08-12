<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tipo_equipos')) {
            Schema::table('tipo_equipos', function (Blueprint $table) {
                if (!Schema::hasColumn('tipo_equipos', 'empresa_id')) {
                    $table->unsignedBigInteger('empresa_id')->nullable()->after('id');
                }
            });
            try {
                Schema::table('tipo_equipos', function (Blueprint $table) {
                    $table->dropUnique('tipo_equipos_nombre_unique');
                });
            } catch (\Throwable $e) {
                try {
                    Schema::table('tipo_equipos', function (Blueprint $table) {
                        $table->dropUnique(['nombre']);
                    });
                } catch (\Throwable $e2) {
                    // Ignorar si no existe
                }
            }
            Schema::table('tipo_equipos', function (Blueprint $table) {
                $table->unique(['empresa_id', 'nombre']);
            });
        }

        if (Schema::hasTable('clase_equipos')) {
            Schema::table('clase_equipos', function (Blueprint $table) {
                if (!Schema::hasColumn('clase_equipos', 'empresa_id')) {
                    $table->unsignedBigInteger('empresa_id')->nullable()->after('id');
                }
            });
        }

        // Asignar datos existentes a la primera empresa (principal)
        $primeraEmpresaId = DB::table('empresas')->orderBy('id')->value('id');
        if ($primeraEmpresaId) {
            DB::table('tipo_equipos')->whereNull('empresa_id')->update(['empresa_id' => $primeraEmpresaId]);
            DB::table('clase_equipos')->whereNull('empresa_id')->update(['empresa_id' => $primeraEmpresaId]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tipo_equipos')) {
            try {
                Schema::table('tipo_equipos', function (Blueprint $table) {
                    $table->dropUnique(['empresa_id', 'nombre']);
                });
            } catch (\Throwable $e) {
                //
            }
            if (Schema::hasColumn('tipo_equipos', 'empresa_id')) {
                Schema::table('tipo_equipos', function (Blueprint $table) {
                    $table->dropColumn('empresa_id');
                });
            }
        }
        if (Schema::hasTable('clase_equipos') && Schema::hasColumn('clase_equipos', 'empresa_id')) {
            Schema::table('clase_equipos', function (Blueprint $table) {
                $table->dropColumn('empresa_id');
            });
        }
    }
};
