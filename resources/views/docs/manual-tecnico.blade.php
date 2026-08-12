<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual técnico — {{ $appName }}</title>
    <style>
        :root { --fg: #0f172a; --muted: #475569; --border: #e2e8f0; --bg: #ffffff; --accent: #0369a1; }
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, Arial, sans-serif; color: var(--fg); background: var(--bg); line-height: 1.55; margin: 0; padding: 2rem 1.5rem 4rem; max-width: 980px; margin-left: auto; margin-right: auto; }
        h1 { font-size: 1.75rem; margin: 0 0 0.5rem; }
        h2 { font-size: 1.2rem; margin: 2rem 0 0.75rem; padding-bottom: 0.35rem; border-bottom: 2px solid var(--border); color: var(--accent); }
        h3 { font-size: 1rem; margin: 1.25rem 0 0.5rem; }
        p, li { color: var(--fg); }
        .muted { color: var(--muted); font-size: 0.9rem; }
        table { width: 100%; border-collapse: collapse; margin: 0.75rem 0 1.25rem; font-size: 0.85rem; }
        th, td { border: 1px solid var(--border); padding: 0.45rem 0.55rem; vertical-align: top; }
        th { background: #f8fafc; text-align: left; }
        code, pre { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.82rem; }
        pre { background: #0f172a; color: #e2e8f0; padding: 0.85rem 1rem; border-radius: 8px; overflow-x: auto; }
        .tag { display: inline-block; padding: 0.1rem 0.45rem; border-radius: 4px; background: #e0f2fe; color: #075985; font-size: 0.75rem; margin-right: 0.25rem; }
        @media print {
            body { padding: 0; max-width: none; }
            a[href]::after { content: ''; }
            pre { white-space: pre-wrap; word-break: break-word; }
        }
    </style>
</head>
<body>
@include('docs.partials.manual-tecnico-body', ['isPdf' => false])
</body>
</html>
