<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsurePreventionWorldCommand extends Command
{
    protected $signature = 'sams:ensure-prevention-world';

    protected $description = 'Consolidar Prevention World como única empresa base y limpiar el resto';

    public function handle(): int
    {
        $pw = Empresa::query()
            ->whereRaw('LOWER(nombre) LIKE ?', ['%prevention world%'])
            ->orderBy('id')
            ->first();

        if (!$pw) {
            $pw = Empresa::query()->create([
                'nombre' => 'Prevention World QHSE S.A.S.',
                'prefijo' => 'PWORLDS',
                'nit' => '900000000-1',
                'activo' => true,
                'modulos' => null,
            ]);
            $this->info('Empresa Prevention World creada.');
        }

        $pwId = (int) $pw->id;

        $tablesWithEmpresa = [
            'aviso_empresas',
            'sedes',
            'bodegas',
            'tipo_equipos',
            'clase_equipos',
            'equipos',
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($tablesWithEmpresa as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'empresa_id')) {
                continue;
            }
            DB::table($table)->where('empresa_id', '!=', $pwId)->delete();
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'empresa_id')) {
            User::query()->whereNotNull('empresa_id')->where('empresa_id', '!=', $pwId)->update(['empresa_id' => $pwId]);
        }

        Empresa::query()->where('id', '!=', $pwId)->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->info("Quedó Prevention World como única empresa (id={$pwId}).");

        return self::SUCCESS;
    }
}
