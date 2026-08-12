<?php

namespace Database\Seeders;

use App\Models\Cargo;
use App\Models\Empresa;
use App\Models\Grupo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->ensurePlaceholdersExist();

        $roleAdminGlobal = Role::query()->where('name', 'administrador')->first();
        $cargoAdmin = Cargo::query()->where('name', 'Administrador')->first();
        $grupoA = Grupo::query()->where('name', 'Grupo A')->first();

        // Super Admin global solicitado
        User::query()->updateOrCreate(
            ['email' => 'danith2468@gmail.com'],
            [
                'codigo' => 'ADM-0000',
                'name' => 'Super',
                'last_name' => 'Admin',
                'password' => Hash::make('1079176426'),
                'role_id' => $roleAdminGlobal?->id,
                'cargo_id' => $cargoAdmin?->id,
                'grupo_id' => $grupoA?->id,
                'empresa_id' => null,
                'active' => true,
                // Por seguridad, puedes ponerlo en true para forzar cambio al primer ingreso.
                'must_change_password' => false,
                'document_type' => 'CC',
                'document_number' => '1079176426',
                'photo' => 'seed/placeholders/user.png',
                'signature' => 'seed/placeholders/signature.png',
            ]
        );

        // Usuario global (matriz)
        User::query()->firstOrCreate(
            ['email' => 'admin@sams.test'],
            [
                'codigo' => 'ADM-0001',
                'name' => 'Admin',
                'last_name' => 'Global',
                'password' => Hash::make('Admin123*'),
                'role_id' => $roleAdminGlobal?->id,
                'cargo_id' => $cargoAdmin?->id,
                'grupo_id' => $grupoA?->id,
                'empresa_id' => null,
                'active' => true,
                'must_change_password' => false,
                'document_type' => 'CC',
                'document_number' => '1000000000',
                'photo' => 'seed/placeholders/user.png',
                'signature' => 'seed/placeholders/signature.png',
            ]
        );

        $empresas = Empresa::query()->orderBy('id')->get();
        foreach ($empresas as $empresa) {
            $prefijo = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $empresa->prefijo)) ?: ('EMP' . $empresa->id);

            $roleEmpresaAdmin = Role::query()->where('name', $prefijo . '-ADMIN')->first();
            $roleEmpresaLector = Role::query()->where('name', $prefijo . '-LECTOR')->first();

            User::query()->firstOrCreate(
                ['email' => strtolower($prefijo) . '.admin@sams.test'],
                [
                    'codigo' => $prefijo . '-U-0001',
                    'name' => 'Admin',
                    'last_name' => $empresa->nombre,
                    'password' => Hash::make('Admin123*'),
                    'role_id' => $roleEmpresaAdmin?->id,
                    'cargo_id' => $cargoAdmin?->id,
                    'grupo_id' => $grupoA?->id,
                    'empresa_id' => $empresa->id,
                    'active' => true,
                    'must_change_password' => false,
                    'document_type' => 'CC',
                    'document_number' => (string) (2000000000 + $empresa->id),
                    'photo' => 'seed/placeholders/user.png',
                    'signature' => 'seed/placeholders/signature.png',
                ]
            );

            User::query()->firstOrCreate(
                ['email' => strtolower($prefijo) . '.lector@sams.test'],
                [
                    'codigo' => $prefijo . '-U-0002',
                    'name' => 'Lector',
                    'last_name' => $empresa->nombre,
                    'password' => Hash::make('Admin123*'),
                    'role_id' => $roleEmpresaLector?->id,
                    'cargo_id' => $cargoAdmin?->id,
                    'grupo_id' => $grupoA?->id,
                    'empresa_id' => $empresa->id,
                    'active' => true,
                    'must_change_password' => false,
                    'document_type' => 'CC',
                    'document_number' => (string) (3000000000 + $empresa->id),
                    'photo' => 'seed/placeholders/user.png',
                    'signature' => 'seed/placeholders/signature.png',
                ]
            );

            // Usuarios extra
            User::factory()
                ->count(8)
                ->create([
                    'empresa_id' => $empresa->id,
                    'role_id' => $roleEmpresaLector?->id,
                    'cargo_id' => $cargoAdmin?->id,
                    'grupo_id' => $grupoA?->id,
                    'active' => true,
                    'must_change_password' => false,
                    'document_type' => 'CC',
                    'document_number' => null,
                    'photo' => 'seed/placeholders/user.png',
                    'signature' => 'seed/placeholders/signature.png',
                ]);
        }
    }

    private function ensurePlaceholdersExist(): void
    {
        if (!Storage::disk('public')->exists('seed/placeholders')) {
            Storage::disk('public')->makeDirectory('seed/placeholders');
        }

        // 1x1 PNG transparente
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMB/axH4i0AAAAASUVORK5CYII='
        );

        if (!Storage::disk('public')->exists('seed/placeholders/user.png')) {
            Storage::disk('public')->put('seed/placeholders/user.png', $png);
        }
        if (!Storage::disk('public')->exists('seed/placeholders/signature.png')) {
            Storage::disk('public')->put('seed/placeholders/signature.png', $png);
        }
    }
}

