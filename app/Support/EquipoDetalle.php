<?php

namespace App\Support;

use App\Models\Equipo;

class EquipoDetalle
{
    public static function loadCompleto(Equipo $equipo): Equipo
    {
        $equipo->loadMissing([
            'empresa',
            'sede',
            'bodega',
            'oficina',
            'espacio',
            'fabricante',
            'tipoEquipo',
            'claseEquipo',
            'imagenes',
            'kitItems',
            'asignacion.user',
        ]);

        return $equipo;
    }

    public static function yesNo(?bool $value): string
    {
        return $value ? 'Sí' : 'No';
    }

    public static function formatMoney($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format((float) $value, 0, ',', '.');
    }

    public static function autoFields(Equipo $equipo, ?string $codigoDb): array
    {
        self::loadCompleto($equipo);

        $asignado = $equipo->asignacion?->user;
        $asignadoNombre = $asignado
            ? trim(($asignado->name ?? '') . ' ' . ($asignado->last_name ?? ''))
            : '';

        $ubicacion = match ((string) ($equipo->ubicacion_tipo ?? '')) {
            'bodega' => (string) ($equipo->bodega?->nombre ?? ''),
            'oficina' => (string) ($equipo->oficina?->nombre ?? ''),
            'espacio' => (string) ($equipo->espacio?->nombre ?? ''),
            default => trim(implode(' / ', array_filter([
                $equipo->bodega?->nombre,
                $equipo->oficina?->nombre,
                $equipo->espacio?->nombre,
            ]))),
        };

        $kitItems = $equipo->kitItems
            ->map(fn ($item) => trim((string) ($item->nombre ?? '') . (($item->descripcion ?? '') !== '' ? ' (' . $item->descripcion . ')' : '')))
            ->filter()
            ->implode('; ');

        return [
            'CODIGO_IN' => (string) ($equipo->codigo ?? ''),
            'CODIGO_DB' => $codigoDb ? (string) $codigoDb : '',
            'NOMBRE' => (string) ($equipo->nombre ?? ''),
            'SERIAL' => (string) ($equipo->serial ?? ''),
            'DESCRIPCION' => (string) ($equipo->descripcion ?? ''),
            'EMPRESA' => (string) ($equipo->empresa?->nombre ?? ''),
            'SEDE' => (string) ($equipo->sede?->nombre ?? ''),
            'BODEGA' => (string) ($equipo->bodega?->nombre ?? ''),
            'OFICINA' => (string) ($equipo->oficina?->nombre ?? ''),
            'ESPACIO' => (string) ($equipo->espacio?->nombre ?? ''),
            'UBICACION' => $ubicacion,
            'UBICACION_TIPO' => (string) ($equipo->ubicacion_tipo ?? ''),
            'FABRICANTE' => (string) ($equipo->fabricante?->nombre ?? ''),
            'TIPO' => (string) ($equipo->tipoEquipo?->nombre ?? ''),
            'CLASE' => (string) ($equipo->claseEquipo?->nombre ?? ''),
            'ESTADO_ITEM' => (string) ($equipo->estado_item ?? ''),
            'OBSERVACION' => (string) ($equipo->observacion ?? ''),
            'VIDA_UTIL' => $equipo->vida_util !== null ? ((string) $equipo->vida_util . ' años') : '',
            'FECHA_FABRICACION' => $equipo->fecha_fabricacion ? $equipo->fecha_fabricacion->format('Y-m-d') : '',
            'FECHA_USO' => $equipo->fecha_uso ? $equipo->fecha_uso->format('Y-m-d') : '',
            'FECHA_COMPRA' => $equipo->fecha_compra ? $equipo->fecha_compra->format('Y-m-d') : '',
            'TIPO_USO' => (string) (($equipo->tipo_uso === 'Otro' && $equipo->tipo_uso_otro)
                ? $equipo->tipo_uso_otro
                : ($equipo->tipo_uso ?? '')),
            'FACTURA' => (string) ($equipo->numero_factura ?? ''),
            'LOTE' => (string) ($equipo->lote ?? ''),
            'VALOR' => self::formatMoney($equipo->valor_equipo),
            'ESPECIFICACIONES' => (string) ($equipo->especificaciones_tecnicas ?? ''),
            'CERTIFICACION' => (string) ($equipo->certificacion_descripcion ?? ''),
            'RESISTENCIA' => (string) ($equipo->resistencia_descripcion ?? ''),
            'TIENE_RESISTENCIA' => self::yesNo((bool) $equipo->tiene_resistencia),
            'MANUAL_FABRICANTE' => self::yesNo((bool) $equipo->tiene_manual_fabricante),
            'CERT_FABRICANTE' => self::yesNo((bool) $equipo->tiene_certificacion_fabricante),
            'ES_KIT' => self::yesNo((bool) $equipo->es_kit),
            'KIT_NOMBRE' => (string) ($equipo->kit_nombre ?? ''),
            'KIT_CANTIDAD' => $equipo->kit_cantidad !== null ? (string) $equipo->kit_cantidad : '',
            'KIT_ITEMS' => $kitItems,
            'ASIGNADO_A' => $asignadoNombre,
            'ACTIVO' => self::yesNo((bool) $equipo->activo),
        ];
    }

    public static function ficha(Equipo $equipo): array
    {
        self::loadCompleto($equipo);

        $imgEtiqueta = $equipo->imagenes->firstWhere('tipo', 'etiqueta');
        $imgGeneral = $equipo->imagenes->firstWhere('tipo', 'general');
        $imgPath = $imgEtiqueta?->path ?: $imgGeneral?->path;
        $asignado = $equipo->asignacion?->user;

        $fields = [
            ['label' => 'Código', 'value' => (string) ($equipo->codigo ?? '')],
            ['label' => 'Nombre', 'value' => (string) ($equipo->nombre ?? '')],
            ['label' => 'Serial', 'value' => (string) ($equipo->serial ?? '')],
            ['label' => 'Descripción', 'value' => (string) ($equipo->descripcion ?? '')],
            ['label' => 'Tipo', 'value' => (string) ($equipo->tipoEquipo?->nombre ?? '')],
            ['label' => 'Clase', 'value' => (string) ($equipo->claseEquipo?->nombre ?? '')],
            ['label' => 'Fabricante', 'value' => (string) ($equipo->fabricante?->nombre ?? '')],
            ['label' => 'Estado ítem', 'value' => (string) ($equipo->estado_item ?? '')],
            ['label' => 'Observación', 'value' => (string) ($equipo->observacion ?? '')],
            ['label' => 'Empresa', 'value' => (string) ($equipo->empresa?->nombre ?? '')],
            ['label' => 'Sede', 'value' => (string) ($equipo->sede?->nombre ?? '')],
            ['label' => 'Bodega', 'value' => (string) ($equipo->bodega?->nombre ?? '')],
            ['label' => 'Oficina', 'value' => (string) ($equipo->oficina?->nombre ?? '')],
            ['label' => 'Espacio', 'value' => (string) ($equipo->espacio?->nombre ?? '')],
            ['label' => 'Tipo ubicación', 'value' => (string) ($equipo->ubicacion_tipo ?? '')],
            ['label' => 'Fecha fabricación', 'value' => $equipo->fecha_fabricacion ? $equipo->fecha_fabricacion->format('Y-m-d') : ''],
            ['label' => 'Fecha de uso', 'value' => $equipo->fecha_uso ? $equipo->fecha_uso->format('Y-m-d') : ''],
            ['label' => 'Tipo de uso', 'value' => (string) (($equipo->tipo_uso === 'Otro' && $equipo->tipo_uso_otro)
                ? $equipo->tipo_uso_otro
                : ($equipo->tipo_uso ?? ''))],
            ['label' => 'Vida útil', 'value' => $equipo->vida_util !== null ? ((string) $equipo->vida_util . ' años') : ''],
            ['label' => 'Fecha de compra', 'value' => $equipo->fecha_compra ? $equipo->fecha_compra->format('Y-m-d') : ''],
            ['label' => 'Factura', 'value' => (string) ($equipo->numero_factura ?? '')],
            ['label' => 'Lote', 'value' => (string) ($equipo->lote ?? '')],
            ['label' => 'Valor', 'value' => self::formatMoney($equipo->valor_equipo)],
            ['label' => 'Especificaciones', 'value' => (string) ($equipo->especificaciones_tecnicas ?? '')],
            ['label' => 'Certificación', 'value' => (string) ($equipo->certificacion_descripcion ?? '')],
            ['label' => 'Manual fabricante', 'value' => self::yesNo((bool) $equipo->tiene_manual_fabricante)],
            ['label' => 'Cert. fabricante', 'value' => self::yesNo((bool) $equipo->tiene_certificacion_fabricante)],
            ['label' => 'Resistencia', 'value' => $equipo->tiene_resistencia
                ? ((string) ($equipo->resistencia_descripcion ?: 'Sí'))
                : 'No'],
            ['label' => 'Es kit', 'value' => self::yesNo((bool) $equipo->es_kit)],
            ['label' => 'Nombre kit', 'value' => (string) ($equipo->kit_nombre ?? '')],
            ['label' => 'Cantidad kit', 'value' => $equipo->kit_cantidad !== null ? (string) $equipo->kit_cantidad : ''],
            ['label' => 'Items del kit', 'value' => $equipo->kitItems
                ->map(fn ($item) => trim((string) ($item->nombre ?? '') . (($item->descripcion ?? '') !== '' ? ' (' . $item->descripcion . ')' : '')))
                ->filter()
                ->implode('; ')],
            ['label' => 'Asignado a', 'value' => $asignado
                ? trim(($asignado->name ?? '') . ' ' . ($asignado->last_name ?? ''))
                : ''],
            ['label' => 'Activo', 'value' => self::yesNo((bool) $equipo->activo)],
        ];

        return [
            'titulo' => trim(($equipo->codigo ?? '') . ' — ' . ($equipo->nombre ?? '')),
            'imagen' => $imgPath ? asset('storage/' . $imgPath) : null,
            'campos' => array_values(array_filter($fields, fn ($f) => trim((string) ($f['value'] ?? '')) !== '')),
        ];
    }

    public static function snapshot(Equipo $equipo): array
    {
        self::loadCompleto($equipo);

        return [
            'id' => $equipo->id,
            'attributes' => $equipo->getAttributes(),
            'relaciones' => [
                'empresa' => $equipo->empresa?->nombre,
                'sede' => $equipo->sede?->nombre,
                'bodega' => $equipo->bodega?->nombre,
                'oficina' => $equipo->oficina?->nombre,
                'espacio' => $equipo->espacio?->nombre,
                'fabricante' => $equipo->fabricante?->nombre,
                'tipo' => $equipo->tipoEquipo?->nombre,
                'clase' => $equipo->claseEquipo?->nombre,
            ],
        ];
    }
}
