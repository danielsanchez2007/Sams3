<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual técnico — {{ $appName }}</title>
    <style>
        :root {
            --fg: #0f172a;
            --muted: #475569;
            --border: #cbd5e1;
            --bg: #ffffff;
            --accent: #1a3a6b;
            --accent-light: #eff6ff;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, Roboto, Ubuntu, Cantarell, Noto Sans, Arial, sans-serif;
            color: var(--fg);
            background: var(--bg);
            line-height: 1.6;
            margin: 0;
            padding: 2.5rem 2rem 5rem;
            max-width: 1024px;
            margin-left: auto;
            margin-right: auto;
        }
        .doc-cover {
            border: 2px solid var(--accent);
            border-radius: 12px;
            padding: 2.5rem 2rem;
            text-align: center;
            background: linear-gradient(165deg, #f8fbff 0%, #eff6ff 100%);
            margin-bottom: 2rem;
        }
        .doc-cover-kicker {
            font-size: 0.75rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--accent);
            font-weight: 600;
        }
        .doc-cover-title { font-size: 2rem; font-weight: 700; color: var(--accent); margin: 0.5rem 0; }
        .doc-cover-sub { font-size: 1.1rem; color: var(--muted); margin: 0 0 1rem; }
        .doc-cover-meta { font-size: 0.9rem; color: var(--muted); }
        h1 { font-size: 1.75rem; margin: 0 0 0.5rem; color: var(--accent); }
        h2 {
            font-size: 1.15rem;
            margin: 2.25rem 0 0.85rem;
            padding: 0.35rem 0 0.35rem 0.65rem;
            border-left: 4px solid var(--accent);
            background: var(--accent-light);
            color: var(--accent);
        }
        h3 { font-size: 1rem; margin: 1.25rem 0 0.5rem; color: #1e293b; }
        p, li { color: var(--fg); }
        .muted { color: var(--muted); font-size: 0.9rem; }
        table { width: 100%; border-collapse: collapse; margin: 0.75rem 0 1.25rem; font-size: 0.85rem; }
        th, td { border: 1px solid var(--border); padding: 0.5rem 0.65rem; vertical-align: top; }
        th { background: #f1f5f9; text-align: left; font-weight: 600; }
        table.doc-control th { width: 28%; background: var(--accent); color: #fff; }
        table.doc-control td { background: #fafafa; }
        code, pre { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.82rem; }
        pre {
            background: #0f172a;
            color: #e2e8f0;
            padding: 0.85rem 1rem;
            border-radius: 8px;
            overflow-x: auto;
        }
        .tag {
            display: inline-block;
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
            background: #dbeafe;
            color: #1e40af;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 0.25rem;
        }
        .toc-wrap { border: 1px solid var(--border); border-radius: 8px; padding: 1rem 1.25rem; background: #f8fafc; }
        .toc-table td { border: none; padding: 0.25rem 0; }
        .toc-table a { color: var(--accent); text-decoration: none; }
        .toc-table a:hover { text-decoration: underline; }
        .toc-num { text-align: right; color: var(--muted); width: 2rem; }
        .notice {
            border-left: 4px solid #f59e0b;
            background: #fffbeb;
            padding: 0.75rem 1rem;
            margin: 1rem 0;
            font-size: 0.9rem;
        }
        @media print {
            body { padding: 0; max-width: none; }
            a[href]::after { content: ''; }
            pre { white-space: pre-wrap; word-break: break-word; }
            h2 { page-break-after: avoid; }
        }
    </style>
</head>
<body>
@include('docs.partials.manual-tecnico-body', ['isPdf' => false])
</body>
</html>
