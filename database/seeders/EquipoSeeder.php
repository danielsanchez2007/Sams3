<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Equipo;
use App\Models\TipoEquipo;
use App\Models\ClaseEquipo;

class EquipoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clases = ClaseEquipo::all();
        
        $equiposPorClase = [
            'Laptops' => [
                ['nombre' => 'Laptop Dell XPS 15', 'serial' => 'DELL-XPS-001', 'descripcion' => 'Laptop de alto rendimiento'],
                ['nombre' => 'Laptop HP Pavilion', 'serial' => 'HP-PAV-001', 'descripcion' => 'Laptop para uso general'],
                ['nombre' => 'Laptop Lenovo ThinkPad', 'serial' => 'LENO-TP-001', 'descripcion' => 'Laptop empresarial'],
            ],
            'Desktops' => [
                ['nombre' => 'Desktop Dell OptiPlex', 'serial' => 'DELL-OPT-001', 'descripcion' => 'Desktop de oficina'],
                ['nombre' => 'Desktop HP EliteDesk', 'serial' => 'HP-ELT-001', 'descripcion' => 'Desktop empresarial'],
            ],
            'Láser' => [
                ['nombre' => 'Impresora HP LaserJet', 'serial' => 'HP-LJ-001', 'descripcion' => 'Impresora láser monocromática'],
                ['nombre' => 'Impresora Brother HL', 'serial' => 'BR-HL-001', 'descripcion' => 'Impresora láser pequeña'],
            ],
            'Inkjet' => [
                ['nombre' => 'Impresora Epson Stylus', 'serial' => 'EPS-ST-001', 'descripcion' => 'Impresora de inyección de tinta'],
                ['nombre' => 'Impresora Canon Pixma', 'serial' => 'CAN-PIX-001', 'descripcion' => 'Impresora multifuncional'],
            ],
            'Rack' => [
                ['nombre' => 'Servidor Dell PowerEdge', 'serial' => 'DELL-PE-001', 'descripcion' => 'Servidor rack de 2U'],
                ['nombre' => 'Servidor HPE ProLiant', 'serial' => 'HPE-PL-001', 'descripcion' => 'Servidor rack empresarial'],
            ],
            'Switches' => [
                ['nombre' => 'Switch Cisco Catalyst', 'serial' => 'CIS-CAT-001', 'descripcion' => 'Switch de 24 puertos'],
                ['nombre' => 'Switch TP-Link', 'serial' => 'TPL-SW-001', 'descripcion' => 'Switch gestionable'],
            ],
            'LCD' => [
                ['nombre' => 'Monitor Dell UltraSharp', 'serial' => 'DELL-US-001', 'descripcion' => 'Monitor LCD 24"'],
                ['nombre' => 'Monitor LG IPS', 'serial' => 'LG-IPS-001', 'descripcion' => 'Monitor IPS 27"'],
            ],
            'Teclados' => [
                ['nombre' => 'Teclado Logitech K120', 'serial' => 'LOG-K120-001', 'descripcion' => 'Teclado USB estándar'],
                ['nombre' => 'Teclado mecánico', 'serial' => 'MECH-KB-001', 'descripcion' => 'Teclado mecánico RGB'],
            ],
        ];

        foreach ($clases as $clase) {
            if (isset($equiposPorClase[$clase->nombre])) {
                foreach ($equiposPorClase[$clase->nombre] as $equipoData) {
                    Equipo::create([
                        'nombre' => $equipoData['nombre'],
                        'serial' => $equipoData['serial'],
                        'descripcion' => $equipoData['descripcion'],
                        'activo' => true,
                        'tipo_equipo_id' => $clase->tipo_equipo_id,
                        'clase_equipo_id' => $clase->id,
                    ]);
                }
            }
        }
    }
}
