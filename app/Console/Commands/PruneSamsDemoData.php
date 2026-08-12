<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina datos operativos y usuarios no administradores.
 * Conserva: administrador global (sin empresa), admin por empresa, rol adminoficina.
 */
class PruneSamsDemoData extends Command
{
    protected $signature = 'sams:prune-demo-data
                            {--force : Ejecutar sin confirmación (obligatorio en no interactivo)}';

    protected $description = 'Borra equipos, asignaciones, préstamos, códigos reutilizables y usuarios que no sean administradores de sistema/oficina/empresa.';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('¿Seguro? Se borrarán equipos, asignaciones, préstamos y usuarios que no sean admins.', false)) {
            $this->warn('Cancelado.');

            return self::SUCCESS;
        }

        $keepIds = $this->resolveUserIdsToKeep();
        if ($keepIds->isEmpty()) {
            $this->error('No se encontró ningún usuario administrador u adminoficina. No se borró nada.');

            return self::FAILURE;
        }

        $this->info('Usuarios a conservar: ' . $keepIds->count() . ' (ids: ' . $keepIds->implode(', ') . ')');

        // No usar DB::transaction(): en MySQL SET FOREIGN_KEY_CHECKS hace commit implícito.
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        try {
            $this->purgeOperationalTables();
            $this->repointEmpresaDefaults($keepIds);
            $this->deleteNonKeptUsers($keepIds);
            $this->resetAutoIncrements();
        } finally {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->delete();
        }

        $this->info('Listo: datos operativos eliminados y AUTO_INCREMENT reiniciado en tablas clave.');
        $this->comment('Los nuevos códigos de inventario seguirán 0001, 0002… al crear equipos (por empresa).');

        return self::SUCCESS;
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function resolveUserIdsToKeep(): \Illuminate\Support\Collection
    {
        return User::query()
            ->with('role')
            ->get()
            ->filter(function (User $u) {
                $name = strtolower(trim((string) ($u->role?->name ?? '')));

                if ($name === 'adminoficina') {
                    return true;
                }
                if (! str_contains($name, 'administrador')) {
                    return false;
                }

                return true;
            })
            ->pluck('id')
            ->unique()
            ->values();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $keepIds
     */
    private function repointEmpresaDefaults(\Illuminate\Support\Collection $keepIds): void
    {
        if (! Schema::hasTable('empresas')) {
            return;
        }

        foreach (Empresa::query()->get(['id', 'default_user_id']) as $emp) {
            $defaultId = (int) ($emp->default_user_id ?? 0);
            if ($defaultId && ! $keepIds->contains($defaultId)) {
                $replacement = User::query()
                    ->where('empresa_id', $emp->id)
                    ->whereIn('id', $keepIds)
                    ->orderBy('id')
                    ->value('id');
                DB::table('empresas')->where('id', $emp->id)->update([
                    'default_user_id' => $replacement,
                ]);
            }
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $keepIds
     */
    private function deleteNonKeptUsers(\Illuminate\Support\Collection $keepIds): void
    {
        User::query()->whereNotIn('id', $keepIds->all())->delete();
    }

    private function purgeOperationalTables(): void
    {
        $ordered = [
            'prestamo_temporal_items',
            'prestamos_temporales',
            'equipo_asignacion_solicitud_items',
            'equipo_asignacion_solicitudes',
            'equipo_asignaciones',
            'equipo_inspecciones',
            'hoja_vida_documentos',
            'historial_mantenimientos',
            'auditoria_equipos',
            'equipos_baja',
            'equipos_auditoria_traspasos',
            'material_didactico_traspasos',
            'equipo_archivos',
            'equipo_imagenes',
            'equipo_kit_items',
            'equipos',
            'codigos_reutilizables',
            'aviso_empresas',
        ];

        foreach ($ordered as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }

    private function resetAutoIncrements(): void
    {
        $tables = [
            'equipos',
            'equipo_imagenes',
            'equipo_archivos',
            'equipo_kit_items',
            'equipo_asignaciones',
            'equipo_asignacion_solicitudes',
            'equipo_asignacion_solicitud_items',
            'equipo_inspecciones',
            'prestamos_temporales',
            'prestamo_temporal_items',
            'hoja_vida_documentos',
            'codigos_reutilizables',
            'aviso_empresas',
            'equipos_baja',
            'historial_mantenimientos',
        ];

        $driver = Schema::getConnection()->getDriverName();
        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE `' . $table . '` AUTO_INCREMENT = 1');
            }
        }

        if ($driver === 'mysql' && Schema::hasTable('users')) {
            $max = (int) DB::table('users')->max('id');
            $next = max(1, $max + 1);
            DB::statement('ALTER TABLE `users` AUTO_INCREMENT = ' . $next);
        }
    }
}
