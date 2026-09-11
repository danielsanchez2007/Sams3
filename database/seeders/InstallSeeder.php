<?php

namespace Database\Seeders;

use App\Models\Empresa;
use Illuminate\Database\Seeder;

class InstallSeeder extends Seeder
{
    public function run(): void
    {
        Empresa::query()->firstOrCreate(
            ['nit' => '900999888-1'],
            [
                'nombre' => 'Prevention World',
                'direccion' => 'Sede principal',
                'telefono' => '0000000',
                'email' => 'info@preventionworld.test',
                'sitio_web' => 'https://preventionworld.test',
                'activo' => true,
                'prefijo' => 'PW',
                'modulos' => [
                    'dashboard' => 'edit',
                    'users' => 'edit',
                    'roles' => 'edit',
                    'cargos' => 'edit',
                    'grupos' => 'edit',
                    'fabricantes' => 'edit',
                    'equipos' => 'edit',
                    'empresa' => 'edit',
                    'hoja_vida' => 'edit',
                    'inspeccion' => 'edit',
                    'exportar' => 'edit',
                    'asignar' => 'edit',
                    'configuracion' => 'edit',
                    'material_didactico' => 'edit',
                    'auditoria' => 'edit',
                    'equipos_baja' => 'edit',
                    'prestamos_temporales' => 'edit',
                ],
            ]
        );

        $this->call([
            UbicacionesSeeder::class,
            CargoSeeder::class,
            GrupoSeeder::class,
            FabricanteSeeder::class,
            RoleSeeder::class,
            TipoEquipoSeeder::class,
            ClaseEquipoSeeder::class,
        ]);
    }
}
