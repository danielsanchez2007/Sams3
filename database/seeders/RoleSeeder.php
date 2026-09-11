<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Rol global para matriz / superadmin (sin empresa_id en users).
        Role::query()->firstOrCreate(
            ['name' => 'administrador'],
            [
                'description' => 'Administrador global (matriz).',
                'activo' => true,
                'permissions' => [
                    'dashboard',
                    'users',
                    'roles',
                    'cargos',
                    'grupos',
                    'fabricantes',
                    'equipos',
                    'empresa',
                    'hoja_vida',
                    'inspeccion',
                    'exportar',
                    'asignar',
                    'configuracion',
                    'material_didactico',
                    'auditoria',
                    'equipos_baja',
                    'prestamos_temporales',
                ],
            ]
        );

        Role::query()->firstOrCreate(
            ['name' => 'adminoficina'],
            [
                'description' => 'Administrador de oficina.',
                'activo' => true,
                'permissions' => [
                    'dashboard',
                    'equipos',
                    'hoja_vida',
                    'inspeccion',
                    'exportar',
                    'asignar',
                    'prestamos_temporales',
                ],
            ]
        );

        // Roles "scoped" por empresa (formato PREFIJO-ROL).
        $empresas = Empresa::query()->orderBy('id')->get();
        foreach ($empresas as $empresa) {
            $prefijo = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $empresa->prefijo)) ?: ('EMP' . $empresa->id);

            $roles = [
                [
                    'name' => $prefijo . '-ADMIN',
                    'description' => 'Administrador de empresa ' . $empresa->nombre,
                    'permissions' => [
                        'dashboard',
                        'users',
                        'roles',
                        'cargos',
                        'grupos',
                        'fabricantes',
                        'equipos',
                        'empresa',
                        'hoja_vida',
                        'inspeccion',
                        'exportar',
                        'asignar',
                        'configuracion',
                        'material_didactico',
                        'auditoria',
                        'equipos_baja',
                        'prestamos_temporales',
                    ],
                ],
                [
                    'name' => $prefijo . '-LECTOR',
                    'description' => 'Solo lectura (empresa ' . $empresa->nombre . ')',
                    'permissions' => [
                        'dashboard',
                        'equipos',
                        'hoja_vida',
                        'inspeccion',
                        'exportar',
                    ],
                ],
            ];

            foreach ($roles as $r) {
                Role::query()->firstOrCreate(
                    ['name' => $r['name']],
                    [
                        'description' => $r['description'],
                        'activo' => true,
                        'permissions' => $r['permissions'],
                    ]
                );
            }
        }
    }
}

