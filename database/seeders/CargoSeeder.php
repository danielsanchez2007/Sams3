<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cargo;
use App\Models\Empresa;

class CargoSeeder extends Seeder
{
    public function run(): void
    {
        $empresas = Empresa::query()->orderBy('id')->get();
        if ($empresas->isEmpty()) {
            $this->command?->warn('CargoSeeder: no hay empresas; omitiendo.');
            return;
        }

        $cargos = [
            ['name' => 'Administrador', 'description' => 'Administrador del sistema', 'activo' => true],
            ['name' => 'Técnico', 'description' => 'Técnico de mantenimiento', 'activo' => true],
            ['name' => 'Supervisor', 'description' => 'Supervisor de equipos', 'activo' => true],
            ['name' => 'Operador', 'description' => 'Operador de equipos', 'activo' => true],
        ];

        foreach ($empresas as $empresa) {
            foreach ($cargos as $cargo) {
                $payload = $cargo;
                if (\Illuminate\Support\Facades\Schema::hasColumn('cargos', 'empresa_id')) {
                    $payload['empresa_id'] = $empresa->id;
                }

                Cargo::query()->firstOrCreate(
                    (\Illuminate\Support\Facades\Schema::hasColumn('cargos', 'empresa_id'))
                        ? ['empresa_id' => $empresa->id, 'name' => $cargo['name']]
                        : ['name' => $cargo['name']],
                    $payload
                );
            }
        }
    }
}
