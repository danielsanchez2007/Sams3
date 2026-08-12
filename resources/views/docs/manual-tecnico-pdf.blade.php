<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Manual técnico SAMS</title>
    <style>
        @page { margin: 12mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #111827; line-height: 1.45; }
        .pdf-cover {
            border: 2px solid #0369a1;
            border-radius: 4px;
            padding: 22mm 14mm;
            text-align: center;
            background: #f0f9ff;
            margin-bottom: 14px;
        }
        .pdf-cover-kicker { font-size: 9pt; letter-spacing: 0.12em; color: #0369a1; text-transform: uppercase; margin-bottom: 8px; }
        .pdf-cover-title { font-size: 22pt; font-weight: bold; color: #0c4a6e; line-height: 1.15; margin: 0 0 6px 0; }
        .pdf-cover-sub { font-size: 12pt; color: #0f172a; margin: 0 0 14px 0; }
        .pdf-cover-meta { font-size: 9pt; color: #334155; }
        .pdf-break { page-break-after: always; }
        .toc-wrap { border: 1px solid #cbd5e1; background: #f8fafc; padding: 10px 12px; margin: 10px 0 14px 0; }
        .toc-wrap h2 { border-bottom: none; margin-top: 0; }
        .toc-table { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
        .toc-table td { border: none; padding: 3px 4px; vertical-align: top; }
        .toc-table td:first-child { width: 78%; }
        .toc-table a { color: #0369a1; text-decoration: none; }
        .toc-num { text-align: right; color: #64748b; }
        h1 { font-size: 15pt; margin: 0 0 8px 0; color: #0c4a6e; }
        h2 { font-size: 11pt; margin: 14px 0 6px 0; color: #0369a1; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px; page-break-after: avoid; }
        h3 { font-size: 10pt; margin: 10px 0 4px 0; page-break-after: avoid; }
        p, li { margin: 4px 0; }
        .muted { color: #475569; font-size: 8.5pt; }
        .tag { font-weight: bold; color: #0369a1; }
        table.data { width: 100%; border-collapse: collapse; margin: 6px 0 10px 0; font-size: 7.5pt; }
        table.data th, table.data td { border: 1px solid #cbd5e1; padding: 3px 4px; vertical-align: top; }
        table.data th { background: #f1f5f9; text-align: left; }
        table.data td.col-action { word-break: break-all; font-size: 7pt; }
        /* Tabla de rutas en PDF horizontal (fragmento dedicado) */
        body.pdf-landscape-routes { font-size: 8.5pt; }
        body.pdf-landscape-routes h2 { font-size: 12pt; }
        body.pdf-landscape-routes table.data-routes { font-size: 8pt; }
        body.pdf-landscape-routes table.data-routes th:nth-child(1),
        body.pdf-landscape-routes table.data-routes td:nth-child(1) { width: 10%; }
        body.pdf-landscape-routes table.data-routes th:nth-child(2),
        body.pdf-landscape-routes table.data-routes td:nth-child(2) { width: 22%; }
        body.pdf-landscape-routes table.data-routes th:nth-child(3),
        body.pdf-landscape-routes table.data-routes td:nth-child(3) { width: 22%; }
        body.pdf-landscape-routes table.data-routes th:nth-child(4),
        body.pdf-landscape-routes table.data-routes td:nth-child(4) { width: 46%; }
        body.pdf-landscape-routes table.data-routes td.col-action { font-size: 7.5pt; word-break: break-word; white-space: normal; }
        table { width: 100%; border-collapse: collapse; margin: 6px 0 10px 0; font-size: 8pt; page-break-inside: auto; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 5px; vertical-align: top; }
        th { background: #f1f5f9; text-align: left; }
        code, pre { font-family: DejaVu Sans Mono, monospace; font-size: 7.5pt; }
        pre { background: #f8fafc; border: 1px solid #e2e8f0; padding: 6px 8px; white-space: pre-wrap; word-wrap: break-word; }
        ul { padding-left: 18px; margin: 4px 0; }
        header.doc-head { margin-bottom: 12px; }
        footer.muted { font-size: 8pt; }
    </style>
</head>
<body class="{{ !empty($pdfLandscape) ? 'pdf-landscape-routes' : '' }}">
@include('docs.partials.manual-tecnico-body', [
    'isPdf' => true,
    'pdfSection' => $pdfSection ?? 'all',
])
</body>
</html>
