<?php

namespace Database\Seeders;

use App\Models\ClaseEquipo;
use App\Models\Equipo;
use App\Models\Empresa;
use App\Models\Sede;
use App\Models\TipoEquipo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 20 equipos de demostración exclusivos de oficina (tipos/clases "Oficina - ...").
 * Reinicia seriales SEED-OFC-* y numeración IN desde 0001 por alias de tipo.
 *
 * php artisan db:seed --class=OfficeDemoEquiposSeeder
 */
class OfficeDemoEquiposSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::query()
            ->whereRaw('LOWER(nombre) like ?', ['%prevention%world%'])
            ->orderBy('id')
            ->first() ?? Empresa::query()->orderBy('id')->first();
        if (!$empresa) {
            $this->command?->warn('No hay empresas en la base de datos; omitiendo OfficeDemoEquiposSeeder.');

            return;
        }

        $prefijo = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $empresa->prefijo)) ?: 'PWSS';

        $this->limpiarEquiposDemoAnteriores();

        $sedes = Sede::query()->where('empresa_id', $empresa->id)->orderBy('id')->pluck('id')->all();
        $sedeIds = $sedes === [] ? [null] : $sedes;

        $tiposDef = [
            [
                'nombre' => 'Oficina - Tecnología',
                'alias' => 'TEC',
                'descripcion' => 'Equipos informáticos y periféricos de oficina.',
                'clases' => [
                    ['nombre' => 'Computadores', 'items' => [
                        ['s' => 'SEED-OFC-001', 'n' => 'Laptop Dell Latitude 5420', 'd' => 'Equipo portátil administración.'],
                        ['s' => 'SEED-OFC-002', 'n' => 'PC torre HP ProDesk', 'd' => 'Estación de trabajo contabilidad.'],
                        ['s' => 'SEED-OFC-003', 'n' => 'Mini PC Intel NUC', 'd' => 'Sala de reuniones.'],
                    ]],
                    ['nombre' => 'Monitores', 'items' => [
                        ['s' => 'SEED-OFC-004', 'n' => 'Monitor LG 24 pulgadas', 'd' => 'Pantalla Full HD.'],
                        ['s' => 'SEED-OFC-005', 'n' => 'Monitor Samsung 27"', 'd' => 'Diseño y planos.'],
                    ]],
                    ['nombre' => 'Periféricos', 'items' => [
                        ['s' => 'SEED-OFC-006', 'n' => 'Mouse inalámbrico Logitech', 'd' => 'Uso general oficina.'],
                        ['s' => 'SEED-OFC-007', 'n' => 'Teclado mecánico USB', 'd' => 'Área sistemas.'],
                        ['s' => 'SEED-OFC-008', 'n' => 'Webcam HD reunión', 'd' => 'Videoconferencias.'],
                    ]],
                    ['nombre' => 'Impresión', 'items' => [
                        ['s' => 'SEED-OFC-009', 'n' => 'Impresora multifuncional HP LaserJet', 'd' => 'Área administrativa.'],
                        ['s' => 'SEED-OFC-010', 'n' => 'Escáner documental Fujitsu', 'd' => 'Digitalización archivo.'],
                    ]],
                ],
            ],
            [
                'nombre' => 'Oficina - Mobiliario',
                'alias' => 'MOB',
                'descripcion' => 'Escritorios, sillas y almacenamiento.',
                'clases' => [
                    ['nombre' => 'Escritorios', 'items' => [
                        ['s' => 'SEED-OFC-011', 'n' => 'Escritorio en L melamina blanca', 'd' => 'Open space zona A.'],
                        ['s' => 'SEED-OFC-012', 'n' => 'Escritorio gerencia nogal', 'd' => 'Oficina directiva.'],
                    ]],
                    ['nombre' => 'Sillas', 'items' => [
                        ['s' => 'SEED-OFC-013', 'n' => 'Silla ergonómica con cabecero', 'd' => 'Puestos larga jornada.'],
                        ['s' => 'SEED-OFC-014', 'n' => 'Silla visitante apilable (juego 4)', 'd' => 'Sala de espera.'],
                    ]],
                    ['nombre' => 'Armarios', 'items' => [
                        ['s' => 'SEED-OFC-015', 'n' => 'Armario archivador 2 puertas', 'd' => 'Pasillo administración.'],
                        ['s' => 'SEED-OFC-016', 'n' => 'Estantería metálica 5 niveles', 'd' => 'Bodega interna oficina.'],
                    ]],
                ],
            ],
            [
                'nombre' => 'Oficina - Comunicaciones',
                'alias' => 'COM',
                'descripcion' => 'Telefonía y audio para reuniones.',
                'clases' => [
                    ['nombre' => 'Telefonía IP', 'items' => [
                        ['s' => 'SEED-OFC-017', 'n' => 'Teléfono IP Yealink escritorio', 'd' => 'Extensión 201.'],
                        ['s' => 'SEED-OFC-018', 'n' => 'Teléfono IP recepción', 'd' => 'Centralita virtual.'],
                    ]],
                    ['nombre' => 'Audio reunión', 'items' => [
                        ['s' => 'SEED-OFC-019', 'n' => 'Altavoz manos libres conferencia', 'd' => 'Sala híbrida.'],
                    ]],
                ],
            ],
            [
                'nombre' => 'Oficina - Enfermería',
                'alias' => 'ENF',
                'descripcion' => 'Botiquines y primeros auxilios en oficina.',
                'clases' => [
                    ['nombre' => 'Botiquines', 'items' => [
                        ['s' => 'SEED-OFC-020', 'n' => 'Botiquín principal recepción', 'd' => 'Kit estándar ANSI.'],
                    ]],
                ],
            ],
        ];

        DB::transaction(function () use ($empresa, $prefijo, $sedeIds, $tiposDef) {
            $sedeCount = count($sedeIds);
            $itemIndex = 0;

            foreach ($tiposDef as $tdef) {
                $tipo = TipoEquipo::query()->firstOrCreate(
                    [
                        'empresa_id' => $empresa->id,
                        'nombre' => $tdef['nombre'],
                    ],
                    [
                        'alias' => $tdef['alias'],
                        'descripcion' => $tdef['descripcion'],
                        'activo' => true,
                    ]
                );

                foreach ($tdef['clases'] as $cdef) {
                    $clase = ClaseEquipo::query()->firstOrCreate(
                        [
                            'empresa_id' => $empresa->id,
                            'tipo_equipo_id' => $tipo->id,
                            'nombre' => $cdef['nombre'],
                        ],
                        [
                            'alias' => null,
                            'descripcion' => null,
                            'activo' => true,
                        ]
                    );

                    foreach ($cdef['items'] as $row) {
                        $sedeId = $sedeIds[$itemIndex % $sedeCount];
                        $itemIndex++;

                        Equipo::query()->create([
                            'nombre' => $row['n'],
                            'serial' => $row['s'],
                            'descripcion' => $row['d'],
                            'activo' => true,
                            'tipo_equipo_id' => $tipo->id,
                            'clase_equipo_id' => $clase->id,
                            'codigo' => null,
                            'vida_util' => null,
                            'estado_item' => 'bueno',
                            'observacion' => null,
                            'fabricante_id' => null,
                            'empresa_id' => $empresa->id,
                            'sede_id' => $sedeId,
                            'va_a_bodega' => false,
                            'bodega_id' => null,
                            'valor_equipo' => null,
                            'es_kit' => false,
                        ]);
                    }
                }
            }
        });

        $this->normalizeSeededCodesForEmpresa($empresa->id, $prefijo);

        $this->command?->info('OfficeDemoEquiposSeeder: 20 equipos de oficina creados (SEED-OFC-001 … SEED-OFC-020).');
    }

    private function limpiarEquiposDemoAnteriores(): void
    {
        Equipo::withTrashed()
            ->where('serial', 'like', 'SEED-OFC-%')
            ->get()
            ->each(fn (Equipo $equipo) => $equipo->forceDelete());
    }

    /**
     * Secuencia global IN-0001, IN-0002…; segmento central = alias del tipo (TEC, MOB, …).
     */
    private function normalizeSeededCodesForEmpresa(int $empresaId, string $prefijo): void
    {
        $seeded = Equipo::query()
            ->with('tipoEquipo:id,alias,nombre')
            ->where('serial', 'like', 'SEED-OFC-%')
            ->orderBy('serial')
            ->get();

        if ($seeded->isEmpty()) {
            return;
        }

        $seq = 0;
        foreach ($seeded as $equipo) {
            $seq++;
            $aliasRaw = (string) ($equipo->tipoEquipo?->alias ?: $equipo->tipoEquipo?->nombre ?: 'GEN');
            $alias = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $aliasRaw) ?: 'GEN', 0, 4));
            $codigo = $prefijo . '-' . $alias . '-IN-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);

            $equipo->forceFill([
                'empresa_id' => $empresaId,
                'codigo' => $codigo,
                'serial' => $equipo->serial,
            ])->save();
        }
    }
}
