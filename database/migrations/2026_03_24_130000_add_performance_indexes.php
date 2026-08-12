<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $indexName): bool
    {
        $rows = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return !empty($rows);
    }

    public function up(): void
    {
        if (Schema::hasTable('equipos')) {
            Schema::table('equipos', function (Blueprint $table) {
                if (Schema::hasColumn('equipos', 'empresa_id') && !$this->indexExists('equipos', 'idx_equipos_empresa_activo')) {
                    $table->index(['empresa_id', 'activo'], 'idx_equipos_empresa_activo');
                }
                if (Schema::hasColumn('equipos', 'empresa_id') && Schema::hasColumn('equipos', 'codigo') && !$this->indexExists('equipos', 'idx_equipos_empresa_codigo')) {
                    $table->index(['empresa_id', 'codigo'], 'idx_equipos_empresa_codigo');
                }
                if (Schema::hasColumn('equipos', 'tipo_equipo_id') && !$this->indexExists('equipos', 'idx_equipos_tipo')) {
                    $table->index('tipo_equipo_id', 'idx_equipos_tipo');
                }
                if (Schema::hasColumn('equipos', 'clase_equipo_id') && !$this->indexExists('equipos', 'idx_equipos_clase')) {
                    $table->index('clase_equipo_id', 'idx_equipos_clase');
                }
                if (Schema::hasColumn('equipos', 'sede_id') && !$this->indexExists('equipos', 'idx_equipos_sede')) {
                    $table->index('sede_id', 'idx_equipos_sede');
                }
                if (Schema::hasColumn('equipos', 'bodega_id') && !$this->indexExists('equipos', 'idx_equipos_bodega')) {
                    $table->index('bodega_id', 'idx_equipos_bodega');
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'empresa_id') && !$this->indexExists('users', 'idx_users_empresa_active')) {
                    $table->index(['empresa_id', 'active'], 'idx_users_empresa_active');
                }
                if (Schema::hasColumn('users', 'empresa_id') && Schema::hasColumn('users', 'codigo') && !$this->indexExists('users', 'idx_users_empresa_codigo')) {
                    $table->index(['empresa_id', 'codigo'], 'idx_users_empresa_codigo');
                }
                if (Schema::hasColumn('users', 'role_id') && !$this->indexExists('users', 'idx_users_role')) {
                    $table->index('role_id', 'idx_users_role');
                }
            });
        }

        if (Schema::hasTable('tipo_equipos')) {
            Schema::table('tipo_equipos', function (Blueprint $table) {
                if (Schema::hasColumn('tipo_equipos', 'empresa_id') && !$this->indexExists('tipo_equipos', 'idx_tipo_equipos_empresa_activo')) {
                    $table->index(['empresa_id', 'activo'], 'idx_tipo_equipos_empresa_activo');
                }
            });
        }

        if (Schema::hasTable('clase_equipos')) {
            Schema::table('clase_equipos', function (Blueprint $table) {
                if (Schema::hasColumn('clase_equipos', 'empresa_id') && !$this->indexExists('clase_equipos', 'idx_clase_equipos_empresa_activo')) {
                    $table->index(['empresa_id', 'activo'], 'idx_clase_equipos_empresa_activo');
                }
                if (Schema::hasColumn('clase_equipos', 'tipo_equipo_id') && !$this->indexExists('clase_equipos', 'idx_clase_equipos_tipo')) {
                    $table->index('tipo_equipo_id', 'idx_clase_equipos_tipo');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('equipos')) {
            Schema::table('equipos', function (Blueprint $table) {
                if ($this->indexExists('equipos', 'idx_equipos_empresa_activo')) {
                    $table->dropIndex('idx_equipos_empresa_activo');
                }
                if ($this->indexExists('equipos', 'idx_equipos_empresa_codigo')) {
                    $table->dropIndex('idx_equipos_empresa_codigo');
                }
                if ($this->indexExists('equipos', 'idx_equipos_tipo')) {
                    $table->dropIndex('idx_equipos_tipo');
                }
                if ($this->indexExists('equipos', 'idx_equipos_clase')) {
                    $table->dropIndex('idx_equipos_clase');
                }
                if ($this->indexExists('equipos', 'idx_equipos_sede')) {
                    $table->dropIndex('idx_equipos_sede');
                }
                if ($this->indexExists('equipos', 'idx_equipos_bodega')) {
                    $table->dropIndex('idx_equipos_bodega');
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if ($this->indexExists('users', 'idx_users_empresa_active')) {
                    $table->dropIndex('idx_users_empresa_active');
                }
                if ($this->indexExists('users', 'idx_users_empresa_codigo')) {
                    $table->dropIndex('idx_users_empresa_codigo');
                }
                if ($this->indexExists('users', 'idx_users_role')) {
                    $table->dropIndex('idx_users_role');
                }
            });
        }

        if (Schema::hasTable('tipo_equipos')) {
            Schema::table('tipo_equipos', function (Blueprint $table) {
                if ($this->indexExists('tipo_equipos', 'idx_tipo_equipos_empresa_activo')) {
                    $table->dropIndex('idx_tipo_equipos_empresa_activo');
                }
            });
        }

        if (Schema::hasTable('clase_equipos')) {
            Schema::table('clase_equipos', function (Blueprint $table) {
                if ($this->indexExists('clase_equipos', 'idx_clase_equipos_empresa_activo')) {
                    $table->dropIndex('idx_clase_equipos_empresa_activo');
                }
                if ($this->indexExists('clase_equipos', 'idx_clase_equipos_tipo')) {
                    $table->dropIndex('idx_clase_equipos_tipo');
                }
            });
        }
    }
};
