@php
    $v = function (string $key) use ($fields): string {
        $value = trim((string) ($fields[$key] ?? ''));
        return $value !== '' ? $value : '—';
    };
    $standalone = $standalone ?? true;
    $forPdf = $forPdf ?? false;
@endphp
@if($standalone)
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Hoja de vida {{ $v('CODIGO') }}</title>
    <style>
        @page { margin: 12mm; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1f2937; margin: 0; padding: 0; }
        .hv-doc { width: 100%; }
        .hv-head { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .hv-head td { border: none; vertical-align: middle; padding: 0 0 10px 0; }
        .hv-logo { max-height: 52px; max-width: 160px; }
        .hv-title { font-size: 18px; font-weight: 700; color: #173461; margin: 0 0 2px 0; }
        .hv-sub { font-size: 11px; color: #64748b; margin: 0; }
        .hv-meta { text-align: right; font-size: 10px; color: #475569; }
        table.hv-grid { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.hv-grid th, table.hv-grid td { border: 1px solid #cbd5e1; padding: 6px 8px; vertical-align: top; }
        table.hv-grid th { width: 32%; background: #173461; color: #fff; font-weight: 600; text-align: left; font-size: 10px; }
        table.hv-grid td { background: #fff; }
        .hv-section { background: #0f766e !important; color: #fff !important; font-size: 12px; font-weight: 700; padding: 7px 8px; margin: 12px 0 0 0; }
        .hv-photos { width: 100%; border-collapse: collapse; }
        .hv-photos td { width: 50%; border: 1px solid #cbd5e1; padding: 8px; text-align: center; vertical-align: top; }
        .hv-photos img { max-width: 100%; max-height: 180px; }
        .hv-caption { font-size: 10px; color: #64748b; margin-top: 4px; }
        .hv-empty { color: #94a3b8; }
        @media print {
            .hv-noprint { display: none !important; }
        }
    </style>
</head>
<body>
@else
<style>
    .hv-doc { width: 100%; font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1f2937; }
    .hv-head { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .hv-head td { border: none; vertical-align: middle; padding: 0 0 10px 0; }
    .hv-logo { max-height: 52px; max-width: 160px; }
    .hv-title { font-size: 18px; font-weight: 700; color: #173461; margin: 0 0 2px 0; }
    .hv-sub { font-size: 11px; color: #64748b; margin: 0; }
    .hv-meta { text-align: right; font-size: 10px; color: #475569; }
    table.hv-grid { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    table.hv-grid th, table.hv-grid td { border: 1px solid #cbd5e1; padding: 6px 8px; vertical-align: top; }
    table.hv-grid th { width: 32%; background: #173461; color: #fff; font-weight: 600; text-align: left; font-size: 10px; }
    .hv-section { background: #0f766e !important; color: #fff !important; font-size: 12px; font-weight: 700; padding: 7px 8px; margin: 12px 0 0 0; }
    .hv-photos { width: 100%; border-collapse: collapse; }
    .hv-photos td { width: 50%; border: 1px solid #cbd5e1; padding: 8px; text-align: center; vertical-align: top; }
    .hv-photos img { max-width: 100%; max-height: 180px; }
    .hv-caption { font-size: 10px; color: #64748b; margin-top: 4px; }
</style>
@endif
<div class="hv-doc">
    <table class="hv-head">
        <tr>
            <td style="width:28%;">
                @if(!empty($logoSrc))
                    <img src="{{ $logoSrc }}" alt="Logo" class="hv-logo">
                @endif
            </td>
            <td>
                <p class="hv-title">Hoja de vida del equipo</p>
                <p class="hv-sub">{{ $v('EMPRESA') }} · Formato oficial del sistema</p>
            </td>
            <td class="hv-meta" style="width:28%;">
                Fecha: {{ $v('FECHA_HOY') }}<br>
                Hora: {{ $v('HORA_HOY') }}
            </td>
        </tr>
    </table>

    <div class="hv-section">Identificación</div>
    <table class="hv-grid">
        <tr><th>Código</th><td>{{ $v('CODIGO') }}</td></tr>
        <tr><th>Nombre</th><td>{{ $v('NOMBRE') }}</td></tr>
        <tr><th>Serial</th><td>{{ $v('SERIAL') }}</td></tr>
        <tr><th>Tipo</th><td>{{ $v('TIPO_EQUIPO') }}</td></tr>
        <tr><th>Clase</th><td>{{ $v('CLASE_EQUIPO') }}</td></tr>
        <tr><th>Descripción</th><td>{{ $v('DESCRIPCION') }}</td></tr>
    </table>

    <div class="hv-section">Ubicación</div>
    <table class="hv-grid">
        <tr><th>Empresa</th><td>{{ $v('EMPRESA') }}</td></tr>
        <tr><th>Sede</th><td>{{ $v('SEDE') }}</td></tr>
        <tr><th>Bodega</th><td>{{ $v('BODEGA') }}</td></tr>
        <tr><th>Ubicación</th><td>{{ $v('UBICACION') }}</td></tr>
    </table>

    <div class="hv-section">Fabricante y fechas</div>
    <table class="hv-grid">
        <tr><th>Fabricante</th><td>{{ $v('FABRICANTE') }}</td></tr>
        <tr><th>Fecha de fabricación</th><td>{{ $v('FECHA_FABRICACION') }}</td></tr>
        <tr><th>Fecha de compra</th><td>{{ $v('FECHA_COMPRA') }}</td></tr>
        <tr><th>Fecha de uso</th><td>{{ $v('FECHA_USO') }}</td></tr>
        <tr><th>Factura</th><td>{{ $v('FACTURA') }}</td></tr>
        <tr><th>Lote</th><td>{{ $v('LOTE') }}</td></tr>
    </table>

    <div class="hv-section">Uso y vida útil</div>
    <table class="hv-grid">
        <tr><th>Uso</th><td>{{ $v('USO') }}</td></tr>
        <tr><th>Vida útil</th><td>{{ $v('VIDA_UTIL') }}</td></tr>
        <tr><th>Estado</th><td>{{ $v('ESTADO_ITEM') }}</td></tr>
        <tr><th>Observación</th><td>{{ $v('OBSERVACION') }}</td></tr>
    </table>

    <div class="hv-section">Certificación y especificaciones</div>
    <table class="hv-grid">
        <tr><th>Certificación</th><td>{{ $v('CERTIFICACION') }}</td></tr>
        <tr><th>Especificaciones técnicas</th><td>{{ $v('ESPECIFICACIONES_TECNICAS') }}</td></tr>
        <tr><th>Resistencia</th><td>{{ $v('RESISTENCIA') }}</td></tr>
    </table>

    <div class="hv-section">Última auditoría</div>
    <table class="hv-grid">
        <tr><th>Fecha auditoría</th><td>{{ $v('FECHA_AUDITORIA') }}</td></tr>
        <tr><th>Cumple normas</th><td>{{ $v('CUMPLE_NORMAS') }}</td></tr>
        <tr><th>Estado físico</th><td>{{ $v('ESTADO_FISICO') }}</td></tr>
        <tr><th>Estado funcional</th><td>{{ $v('ESTADO_FUNCIONAL') }}</td></tr>
        <tr><th>Puntuación</th><td>{{ $v('PUNTUACION') }}</td></tr>
    </table>

    @if(($fotos ?? collect())->isNotEmpty())
        <div class="hv-section">Fotografías</div>
        <table class="hv-photos">
            @foreach($fotos->chunk(2) as $row)
                <tr>
                    @foreach($row as $foto)
                        <td>
                            <img src="{{ $foto['src'] }}" alt="{{ $foto['label'] }}">
                            <div class="hv-caption">{{ $foto['label'] }}</div>
                        </td>
                    @endforeach
                    @if($row->count() === 1)
                        <td></td>
                    @endif
                </tr>
            @endforeach
        </table>
    @endif
</div>
@if($standalone)
@if(request()->boolean('print'))
<script>window.addEventListener('load', function () { window.print(); });</script>
@endif
</body>
</html>
@endif
