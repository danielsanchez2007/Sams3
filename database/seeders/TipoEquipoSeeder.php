<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TipoEquipo;
use App\Models\Empresa;

class TipoEquipoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $empresas = Empresa::query()->orderBy('id')->get();
        if ($empresas->isEmpty()) {
            $this->command?->warn('TipoEquipoSeeder: no hay empresas; omitiendo.');
            return;
        }

        $tipos = [
            [
                'nombre' => 'Computadoras',
                'alias' => 'COMP',
                'descripcion' => 'Equipos de cómputo de escritorio y portátiles',
                'activo' => true,
            ],
            [
                'nombre' => 'Impresoras',
                'alias' => 'IMPR',
                'descripcion' => 'Equipos de impresión y multifuncionales',
                'activo' => true,
            ],
            [
                'nombre' => 'Servidores',
                'alias' => 'SERV',
                'descripcion' => 'Servidores y equipos de infraestructura',
                'activo' => true,
            ],
            [
                'nombre' => 'Redes',
                'alias' => 'RED',
                'descripcion' => 'Equipos de conectividad y redes',
                'activo' => true,
            ],
            [
                'nombre' => 'Monitores',
                'alias' => 'MON',
                'descripcion' => 'Pantallas y monitores',
                'activo' => true,
            ],
            [
                'nombre' => 'Periféricos',
                'alias' => 'PER',
                'descripcion' => 'Teclados, mouse, y otros dispositivos',
                'activo' => true,
            ],
        ];

        foreach ($empresas as $empresa) {
            foreach ($tipos as $tipo) {
                TipoEquipo::query()->firstOrCreate(
                    [
                        'empresa_id' => $empresa->id,
                        'nombre' => $tipo['nombre'],
                    ],
                    [
                        'alias' => $tipo['alias'],
                        'descripcion' => $tipo['descripcion'],
                        'activo' => (bool) $tipo['activo'],
                    ]
                );
            }
        }
    }
}
