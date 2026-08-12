<?php

namespace Database\Seeders;

use App\Models\Empresa;
use Illuminate\Database\Seeder;

class EmpresaSeeder extends Seeder
{
    public function run(): void
    {
        $empresas = [
            [
                'nombre' => 'Prevention World',
                'nit' => '900999888-1',
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
            ],
            [
                'nombre' => 'Empresa Demo SAS',
                'nit' => '901111222-3',
                'direccion' => 'Dirección demo',
                'telefono' => '0000001',
                'email' => 'contacto@empresademo.test',
                'sitio_web' => 'https://empresademo.test',
                'activo' => true,
                'prefijo' => 'DEMO',
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
                    'inspeccion' => 'view',
                    'exportar' => 'edit',
                    'asignar' => 'edit',
                    'configuracion' => 'edit',
                    'material_didactico' => 'edit',
                    'auditoria' => 'view',
                    'equipos_baja' => 'edit',
                    'prestamos_temporales' => 'edit',
                ],
            ],
        ];

        foreach ($empresas as $data) {
            Empresa::query()->firstOrCreate(
                ['nit' => $data['nit']],
                $data
            );
        }
    }
}

