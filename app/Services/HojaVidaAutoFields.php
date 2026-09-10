<?php

namespace App\Services;

use App\Models\AuditoriaEquipo;
use App\Models\Equipo;
use App\Models\User;
use App\Support\SafeStoragePath;
use Illuminate\Support\Facades\Schema;

class HojaVidaAutoFields
{
    /**
     * @return array<string, string>
     */
    public static function forEquipo(Equipo $equipo, ?User $user = null): array
    {
        $equipo->loadMissing(['tipoEquipo', 'claseEquipo', 'empresa', 'sede', 'bodega', 'fabricante']);

        $fechaHoy = now();
        $authUser = $user ?? auth()->user();

        $ubicacion = trim((string) ($equipo->sede?->nombre ?? ''));
        if ($equipo->bodega?->nombre) {
            $ubicacion = trim($ubicacion . ' - ' . (string) $equipo->bodega->nombre);
        }

        $uso = (string) ($equipo->tipo_uso ?? '');
        if (strtolower(trim($uso)) === 'otro' && $equipo->tipo_uso_otro) {
            $uso = (string) $equipo->tipo_uso_otro;
        }

        $auditoria = null;
        if (Schema::hasTable('auditoria_equipos')) {
            $auditoria = AuditoriaEquipo::query()
                ->where('equipo_id', $equipo->id)
                ->orderByDesc('fecha_auditoria')
                ->orderByDesc('id')
                ->first();
        }

        $usuarioNombre = trim((string) ($authUser?->name ?? '') . ' ' . (string) ($authUser?->last_name ?? ''));

        $data = [
            'CODIGO' => (string) $equipo->codigo,
            'CODIGO_IN' => (string) $equipo->codigo,
            'NOMBRE' => (string) $equipo->nombre,
            'NOMBRE_EQUIPO' => (string) $equipo->nombre,
            'SERIAL' => (string) $equipo->serial,
            'SERIE' => (string) $equipo->serial,
            'DESCRIPCION' => (string) ($equipo->descripcion ?? ''),
            'DESCRIPCION_GENERAL' => (string) ($equipo->descripcion ?? ''),
            'TIPO_EQUIPO' => (string) ($equipo->tipoEquipo?->nombre ?? ''),
            'CLASE_EQUIPO' => (string) ($equipo->claseEquipo?->nombre ?? ''),

            'EMPRESA' => (string) ($equipo->empresa?->nombre ?? ''),
            'SEDE' => (string) ($equipo->sede?->nombre ?? ''),
            'BODEGA' => (string) ($equipo->bodega?->nombre ?? ''),
            'UBICACION' => $ubicacion,

            'FABRICANTE' => (string) ($equipo->fabricante?->name ?? ''),

            'FECHA_COMPRA' => $equipo->fecha_compra ? $equipo->fecha_compra->format('Y-m-d') : '',
            'FACTURA' => (string) ($equipo->numero_factura ?? ''),
            'LOTE' => (string) ($equipo->lote ?? ''),

            'USO' => $uso,
            'TIPO_USO' => (string) ($equipo->tipo_uso ?? ''),
            'TIPO_USO_OTRO' => (string) ($equipo->tipo_uso_otro ?? ''),

            'FECHA_FABRICACION' => $equipo->fecha_fabricacion ? $equipo->fecha_fabricacion->format('Y-m-d') : '',
            'FECHA_USO' => $equipo->fecha_uso ? $equipo->fecha_uso->format('Y-m-d') : '',

            'VIDA_UTIL' => $equipo->vida_util !== null ? (string) $equipo->vida_util : '',
            'ESTADO_ITEM' => (string) ($equipo->estado_item ?? ''),
            'OBSERVACION' => (string) ($equipo->observacion ?? ''),

            'CERTIFICACION' => (string) ($equipo->certificacion_descripcion ?? ''),
            'CERTIFICACION_DESCRIPCION' => (string) ($equipo->certificacion_descripcion ?? ''),
            'ESPECIFICACIONES_TECNICAS' => (string) ($equipo->especificaciones_tecnicas ?? ''),

            'TIENE_RESISTENCIA' => (string) ((int) ($equipo->tiene_resistencia ?? 0)),
            'RESISTENCIA' => (string) ($equipo->resistencia_descripcion ?? ''),

            'FECHA_HOY' => $fechaHoy->format('Y-m-d'),
            'HORA_HOY' => $fechaHoy->format('H:i'),
            'USUARIO' => $usuarioNombre !== '' ? $usuarioNombre : (string) ($authUser?->name ?? ''),
        ];

        if ($auditoria) {
            $data['CUMPLE_NORMAS'] = $auditoria->cumple_normas ? 'SI' : 'NO';
            $data['ESTADO_FISICO'] = (string) ($auditoria->estado_fisico ?? '');
            $data['ESTADO_FUNCIONAL'] = (string) ($auditoria->estado_funcional ?? '');
            $data['PUNTUACION'] = $auditoria->puntuacion !== null ? (string) $auditoria->puntuacion : '';
            $data['FECHA_AUDITORIA'] = $auditoria->fecha_auditoria ? $auditoria->fecha_auditoria->format('Y-m-d') : '';
        } else {
            $data['CUMPLE_NORMAS'] = '';
            $data['ESTADO_FISICO'] = '';
            $data['ESTADO_FUNCIONAL'] = '';
            $data['PUNTUACION'] = '';
            $data['FECHA_AUDITORIA'] = '';
        }

        return $data;
    }

    /**
     * Etiquetas de chips para el panel de inspección (sin alias duplicados).
     *
     * @return array<string, string>
     */
    public static function chipLabels(): array
    {
        return [
            'CODIGO' => 'Código',
            'NOMBRE' => 'Nombre',
            'SERIAL' => 'Serial',
            'TIPO_EQUIPO' => 'Tipo',
            'CLASE_EQUIPO' => 'Clase',
            'EMPRESA' => 'Empresa',
            'SEDE' => 'Sede',
            'BODEGA' => 'Bodega',
            'UBICACION' => 'Ubicación',
            'FABRICANTE' => 'Fabricante',
            'FECHA_COMPRA' => 'Fecha de compra',
            'FACTURA' => 'Factura',
            'LOTE' => 'Lote',
            'USO' => 'Uso',
            'FECHA_FABRICACION' => 'Fecha fabricación',
            'FECHA_USO' => 'Fecha de uso',
            'VIDA_UTIL' => 'Vida útil',
            'ESTADO_ITEM' => 'Estado',
            'OBSERVACION' => 'Observación',
            'CERTIFICACION' => 'Certificación',
            'ESPECIFICACIONES_TECNICAS' => 'Especificaciones',
            'CUMPLE_NORMAS' => 'Cumple normas',
            'ESTADO_FISICO' => 'Estado físico',
            'ESTADO_FUNCIONAL' => 'Estado funcional',
            'PUNTUACION' => 'Puntuación',
            'FECHA_AUDITORIA' => 'Fecha auditoría',
            'FECHA_HOY' => 'Fecha de hoy',
            'DESCRIPCION' => 'Descripción',
        ];
    }

    /**
     * @return list<array{key: string, label: string, value: string}>
     */
    public static function chipsForEquipo(Equipo $equipo, ?User $user = null): array
    {
        $fields = self::forEquipo($equipo, $user);
        $chips = [];

        foreach (self::chipLabels() as $key => $label) {
            $chips[] = [
                'key' => $key,
                'label' => $label,
                'value' => (string) ($fields[$key] ?? ''),
            ];
        }

        return $chips;
    }

    public static function imageSrc(?string $relativePath, bool $forPdf = false): ?string
    {
        if (!$relativePath) {
            return null;
        }

        if ($forPdf) {
            return SafeStoragePath::toDataUri($relativePath);
        }

        $relative = SafeStoragePath::relativeWithinPublic($relativePath);

        return $relative ? asset('storage/' . $relative) : null;
    }

    public static function renderHtml(Equipo $equipo, bool $forPdf = false, bool $standalone = true): string
    {
        $equipo->loadMissing([
            'tipoEquipo',
            'claseEquipo',
            'empresa',
            'sede',
            'bodega',
            'fabricante',
            'imagenes',
        ]);

        $fields = self::forEquipo($equipo);
        $empresa = $equipo->empresa;
        $logoPath = $empresa?->logo_principal ?: ($empresa?->logo ?: null);
        $logoSrc = self::imageSrc($logoPath, $forPdf);

        $fotoLabels = [
            'general' => 'Foto general',
            'etiqueta' => 'Foto etiqueta',
            'certificacion_evidencia' => 'Evidencia de certificación',
            'kit_general' => 'Foto kit',
        ];

        $fotos = $equipo->imagenes
            ->filter(fn ($img) => !empty($img->path))
            ->map(function ($img) use ($forPdf, $fotoLabels) {
                $src = self::imageSrc((string) $img->path, $forPdf);
                if (!$src) {
                    return null;
                }

                return [
                    'tipo' => (string) $img->tipo,
                    'label' => $fotoLabels[$img->tipo] ?? 'Foto',
                    'src' => $src,
                ];
            })
            ->filter()
            ->values();

        return view('admin.formatos.sistema-hoja-vida', [
            'equipo' => $equipo,
            'fields' => $fields,
            'empresa' => $empresa,
            'logoSrc' => $logoSrc,
            'fotos' => $fotos,
            'forPdf' => $forPdf,
            'standalone' => $standalone,
        ])->render();
    }
}
