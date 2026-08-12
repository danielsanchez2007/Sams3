<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Fabricante;

class FabricanteSeeder extends Seeder
{
    public function run(): void
    {
        $fabricantes = [
            ['name' => 'Dell', 'description' => 'Fabricante de equipos Dell', 'contacto' => 'support@dell.com', 'telefono' => '1-800-DELL', 'email' => 'support@dell.com', 'activo' => true],
            ['name' => 'HP', 'description' => 'Fabricante de equipos HP', 'contacto' => 'support@hp.com', 'telefono' => '1-800-HP', 'email' => 'support@hp.com', 'activo' => true],
            ['name' => 'Lenovo', 'description' => 'Fabricante de equipos Lenovo', 'contacto' => 'support@lenovo.com', 'telefono' => '1-800-LENOVO', 'email' => 'support@lenovo.com', 'activo' => true],
            ['name' => 'Cisco', 'description' => 'Fabricante de equipos de red', 'contacto' => 'support@cisco.com', 'telefono' => '1-800-CISCO', 'email' => 'support@cisco.com', 'activo' => true],
        ];

        foreach ($fabricantes as $fabricante) {
            Fabricante::query()->firstOrCreate(
                ['name' => $fabricante['name']],
                $fabricante
            );
        }
    }
}
