<?php

namespace Database\Seeders;

use App\Models\Bodega;
use App\Models\Empresa;
use App\Models\Espacio;
use App\Models\Oficina;
use App\Models\Sede;
use Illuminate\Database\Seeder;

class UbicacionesSeeder extends Seeder
{
    public function run(): void
    {
        $empresas = Empresa::query()->orderBy('id')->get();
        if ($empresas->isEmpty()) {
            $this->command?->warn('UbicacionesSeeder: no hay empresas; omitiendo.');
            return;
        }

        foreach ($empresas as $empresa) {
            $sedePrincipal = Sede::query()->firstOrCreate(
                ['empresa_id' => $empresa->id, 'nombre' => 'Sede Principal'],
                [
                    'pais' => 'Colombia',
                    'departamento' => 'Antioquia',
                    'municipio' => 'Medellín',
                    'ciudad' => 'Medellín',
                    'direccion' => 'Dirección sede principal',
                    'google_maps_url' => null,
                    'activo' => true,
                ]
            );

            $sedeSecundaria = Sede::query()->firstOrCreate(
                ['empresa_id' => $empresa->id, 'nombre' => 'Sede Secundaria'],
                [
                    'pais' => 'Colombia',
                    'departamento' => 'Cundinamarca',
                    'municipio' => 'Bogotá',
                    'ciudad' => 'Bogotá',
                    'direccion' => 'Dirección sede secundaria',
                    'google_maps_url' => null,
                    'activo' => true,
                ]
            );

            foreach ([$sedePrincipal, $sedeSecundaria] as $sede) {
                Bodega::query()->firstOrCreate(
                    ['empresa_id' => $empresa->id, 'sede_id' => $sede->id, 'nombre' => 'Bodega General'],
                    [
                        'pais' => $sede->pais,
                        'departamento' => $sede->departamento,
                        'municipio' => $sede->municipio,
                        'ciudad' => $sede->ciudad,
                        'direccion' => $sede->direccion,
                        'google_maps_url' => null,
                        'activo' => true,
                    ]
                );

                Bodega::query()->firstOrCreate(
                    ['empresa_id' => $empresa->id, 'sede_id' => $sede->id, 'nombre' => 'Bodega SST'],
                    [
                        'pais' => $sede->pais,
                        'departamento' => $sede->departamento,
                        'municipio' => $sede->municipio,
                        'ciudad' => $sede->ciudad,
                        'direccion' => $sede->direccion,
                        'google_maps_url' => null,
                        'activo' => true,
                    ]
                );

                Oficina::query()->firstOrCreate(
                    ['empresa_id' => $empresa->id, 'sede_id' => $sede->id, 'nombre' => 'Oficina Administrativa']
                );

                Oficina::query()->firstOrCreate(
                    ['empresa_id' => $empresa->id, 'sede_id' => $sede->id, 'nombre' => 'Oficina Técnica']
                );

                Espacio::query()->firstOrCreate(
                    ['empresa_id' => $empresa->id, 'sede_id' => $sede->id, 'nombre' => 'Sala de Juntas']
                );

                Espacio::query()->firstOrCreate(
                    ['empresa_id' => $empresa->id, 'sede_id' => $sede->id, 'nombre' => 'Recepción']
                );
            }
        }
    }
}

