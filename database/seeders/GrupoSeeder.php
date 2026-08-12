<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Grupo;
use App\Models\Empresa;
use Illuminate\Support\Facades\Schema;

class GrupoSeeder extends Seeder
{
    public function run(): void
    {
        $empresas = Empresa::query()->orderBy('id')->get();
        if ($empresas->isEmpty()) {
            $this->command?->warn('GrupoSeeder: no hay empresas; omitiendo.');
            return;
        }

        $grupos = [
            ['name' => 'Grupo A', 'description' => 'Grupo de mantenimiento A', 'activo' => true],
            ['name' => 'Grupo B', 'description' => 'Grupo de mantenimiento B', 'activo' => true],
            ['name' => 'Grupo C', 'description' => 'Grupo de mantenimiento C', 'activo' => true],
        ];

        foreach ($empresas as $empresa) {
            foreach ($grupos as $grupo) {
                $payload = $grupo;
                if (Schema::hasColumn('grupos', 'empresa_id')) {
                    $payload['empresa_id'] = $empresa->id;
                }

                Grupo::query()->firstOrCreate(
                    (Schema::hasColumn('grupos', 'empresa_id'))
                        ? ['empresa_id' => $empresa->id, 'name' => $grupo['name']]
                        : ['name' => $grupo['name']],
                    $payload
                );
            }
        }
    }
}
