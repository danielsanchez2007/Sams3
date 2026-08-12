<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ClaseEquipo;
use App\Models\TipoEquipo;

class ClaseEquipoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tipos = TipoEquipo::all();
        if ($tipos->isEmpty()) {
            $this->command?->warn('ClaseEquipoSeeder: no hay tipos de equipo; omitiendo.');
            return;
        }
        
        $clasesPorTipo = [
            'Computadoras' => [
                ['nombre' => 'Laptops', 'alias' => 'LAP', 'descripcion' => 'Computadoras portátiles'],
                ['nombre' => 'Desktops', 'alias' => 'DESK', 'descripcion' => 'Computadoras de escritorio'],
                ['nombre' => 'Workstations', 'alias' => 'WKS', 'descripcion' => 'Estaciones de trabajo de alto rendimiento'],
            ],
            'Impresoras' => [
                ['nombre' => 'Láser', 'alias' => 'LAS', 'descripcion' => 'Impresoras láser blanco y negro'],
                ['nombre' => 'Inkjet', 'alias' => 'INK', 'descripcion' => 'Impresoras de inyección de tinta'],
                ['nombre' => 'Multifuncionales', 'alias' => 'MFP', 'descripcion' => 'Impresoras con escáner y copiadora'],
            ],
            'Servidores' => [
                ['nombre' => 'Rack', 'alias' => 'RACK', 'descripcion' => 'Servidores de montaje en rack'],
                ['nombre' => 'Tower', 'alias' => 'TWR', 'descripcion' => 'Servidores de torre'],
                ['nombre' => 'Blade', 'alias' => 'BLD', 'descripcion' => 'Servidores blade'],
            ],
            'Redes' => [
                ['nombre' => 'Switches', 'alias' => 'SW', 'descripcion' => 'Dispositivos de conmutación de red'],
                ['nombre' => 'Routers', 'alias' => 'RTR', 'descripcion' => 'Enrutadores de red'],
                ['nombre' => 'Access Points', 'alias' => 'AP', 'descripcion' => 'Puntos de acceso inalámbricos'],
            ],
            'Monitores' => [
                ['nombre' => 'LCD', 'alias' => 'LCD', 'descripcion' => 'Monitores de pantalla plana LCD'],
                ['nombre' => 'LED', 'alias' => 'LED', 'descripcion' => 'Monitores de pantalla plana LED'],
                ['nombre' => '4K', 'alias' => '4K', 'descripcion' => 'Monitores de alta resolución 4K'],
            ],
            'Periféricos' => [
                ['nombre' => 'Teclados', 'alias' => 'KB', 'descripcion' => 'Dispositivos de entrada de texto'],
                ['nombre' => 'Mouse', 'alias' => 'MSE', 'descripcion' => 'Dispositivos de apuntamiento'],
                ['nombre' => 'Webcams', 'alias' => 'CAM', 'descripcion' => 'Cámaras web para videoconferencias'],
            ],
        ];

        foreach ($tipos as $tipo) {
            if (isset($clasesPorTipo[$tipo->nombre])) {
                foreach ($clasesPorTipo[$tipo->nombre] as $claseData) {
                    ClaseEquipo::query()->firstOrCreate(
                        [
                            'empresa_id' => $tipo->empresa_id,
                            'tipo_equipo_id' => $tipo->id,
                            'nombre' => $claseData['nombre'],
                        ],
                        [
                            'alias' => $claseData['alias'] ?? null,
                            'descripcion' => $claseData['descripcion'] ?? null,
                            'activo' => true,
                        ]
                    );
                }
            }
        }
    }
}
