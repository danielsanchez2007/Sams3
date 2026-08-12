<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Acta de Baja {{ $codigoDb ?? '' }}</title>
    <style>
        @page { margin: 18mm 16mm 20mm 16mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2937;
            line-height: 1.45;
            margin: 0;
        }
        .header {
            border-bottom: 3px solid #0f766e;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .header-table { width: 100%; border-collapse: collapse; }
        .brand {
            font-size: 18px;
            font-weight: bold;
            color: #0f766e;
            letter-spacing: 0.3px;
        }
        .brand-sub { font-size: 10px; color: #64748b; margin-top: 2px; }
        .doc-meta { text-align: right; }
        .doc-title {
            display: inline-block;
            background: #0f766e;
            color: #fff;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 6px 12px;
            border-radius: 3px;
        }
        .doc-code { margin-top: 6px; font-size: 11px; color: #0f766e; font-weight: bold; }
        .doc-date { font-size: 10px; color: #64748b; margin-top: 2px; }
        h2 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #0f766e;
            border-left: 3px solid #0f766e;
            padding-left: 8px;
            margin: 18px 0 8px;
        }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td {
            vertical-align: top;
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            width: 50%;
        }
        .label {
            display: block;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 2px;
        }
        .value { font-size: 11px; font-weight: 600; color: #111827; }
        .box {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 10px 12px;
            background: #f8fafc;
            min-height: 48px;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            background: #fee2e2;
            color: #991b1b;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .sign-table { width: 100%; border-collapse: collapse; margin-top: 36px; }
        .sign-table td {
            width: 50%;
            text-align: center;
            padding: 8px 16px;
            vertical-align: bottom;
        }
        .sign-line {
            border-top: 1px solid #334155;
            margin: 48px 24px 6px;
        }
        .sign-role { font-size: 10px; color: #64748b; }
        .footer {
            position: fixed;
            bottom: -8mm;
            left: 0;
            right: 0;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
            text-align: center;
        }
        .note { font-size: 9px; color: #64748b; margin-top: 14px; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 62%;">
                    <div class="brand">{{ $empresaNombre ?? 'SAMS' }}</div>
                    <div class="brand-sub">Sistema de Administración y Manejo de Seguridad</div>
                    <div class="brand-sub" style="margin-top:6px;">Acta oficial de equipo dado de baja</div>
                </td>
                <td class="doc-meta" style="width: 38%;">
                    <div class="doc-title">Acta de Baja</div>
                    <div class="doc-code">{{ $codigoDb ?? '—' }}</div>
                    <div class="doc-date">Fecha: {{ $fechaBaja ?? now()->format('Y-m-d') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-bottom: 10px;">
        <span class="badge">Equipo dado de baja</span>
    </div>

    <h2>Identificación del equipo</h2>
    <table class="grid">
        <tr>
            <td>
                <span class="label">Código de baja (DB)</span>
                <span class="value">{{ $codigoDb ?? '—' }}</span>
            </td>
            <td>
                <span class="label">Código anterior (IN)</span>
                <span class="value">{{ $codigoIn ?? '—' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Nombre del equipo</span>
                <span class="value">{{ $nombre ?? '—' }}</span>
            </td>
            <td>
                <span class="label">Serial</span>
                <span class="value">{{ $serial ?? '—' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Empresa</span>
                <span class="value">{{ $empresa ?? '—' }}</span>
            </td>
            <td>
                <span class="label">Sede / Bodega</span>
                <span class="value">{{ trim(($sede ?? '') . (($sede ?? '') && ($bodega ?? '') ? ' / ' : '') . ($bodega ?? '')) ?: '—' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Fecha de compra</span>
                <span class="value">{{ $fechaCompra ?? '—' }}</span>
            </td>
            <td>
                <span class="label">Número de factura</span>
                <span class="value">{{ $factura ?: '—' }}</span>
            </td>
        </tr>
    </table>

    <h2>Motivo de la baja</h2>
    <div class="box">{{ $motivo ?: 'Sin motivo registrado.' }}</div>

    @if(!empty($observaciones))
    <h2>Observaciones</h2>
    <div class="box">{{ $observaciones }}</div>
    @endif

    <h2>Responsable del registro</h2>
    <table class="grid">
        <tr>
            <td>
                <span class="label">Registrado por</span>
                <span class="value">{{ $usuarioNombre ?? '—' }}</span>
            </td>
            <td>
                <span class="label">Fecha y hora del registro</span>
                <span class="value">{{ $registradoEn ?? now()->format('Y-m-d H:i') }}</span>
            </td>
        </tr>
    </table>

    <table class="sign-table">
        <tr>
            <td>
                <div class="sign-line"></div>
                <div><strong>Responsable que entrega</strong></div>
                <div class="sign-role">Nombre y firma</div>
            </td>
            <td>
                <div class="sign-line"></div>
                <div><strong>Responsable que recibe / autoriza</strong></div>
                <div class="sign-role">Nombre y firma</div>
            </td>
        </tr>
    </table>

    <p class="note">
        Documento generado automáticamente por SAMS. El código de inventario anterior queda disponible para reutilización
        conforme a las políticas de la empresa. Conserve este archivo como soporte oficial de la baja.
    </p>

    <div class="footer">
        SAMS · Acta de baja {{ $codigoDb ?? '' }} · Generado el {{ now()->format('Y-m-d H:i') }}
    </div>
</body>
</html>
