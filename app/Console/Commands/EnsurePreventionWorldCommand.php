<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsurePreventionWorldCommand extends Command
{
    protected $signature = 'sams:ensure-prevention-world {--force : Confirma la eliminación de otras empresas}';

    protected $description = 'Consolidar Prevention World como única empresa base y limpiar el resto';

    public function handle(): int
    {
        if ($this->laravel->environment('production') && ! $this->option('force')) {
            $this->error('Comando bloqueado en producción. Si realmente debes ejecutarlo, usa --force.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Esto elimina empresas distintas de Prevention World y reasigna usuarios. ¿Continuar?')) {
            $this->warn('Operación cancelada.');

            return self::FAILURE;
        }

        $pw = Empresa::query()
            ->whereRaw('LOWER(nombre) LIKE ?', ['%prevention world%'])
            ->orderBy('id')
            ->first();

        if (!$pw) {
            $pw = new Empresa();
            $pw->fill([
                'nombre' => 'Prevention World QHSE S.A.S.',
                'prefijo' => 'PWORLDS',
                'nit' => '900000000-1',
                'activo' => true,
            ]);
            $pw->forceFill(['modulos' => null]);
            $pw->save();
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
