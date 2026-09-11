<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Instalar SAMS</title>
    <style>
        :root { --navy:#003087; --blue:#2b7de9; --ok:#047857; --bad:#b91c1c; --bg:#f4f7fb; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: Segoe UI, Inter, system-ui, sans-serif; background: var(--bg); color:#0f172a; }
        .wrap { max-width: 880px; margin: 32px auto; padding: 0 16px 48px; }
        .card { background:#fff; border-radius: 20px; padding: 28px; box-shadow: 0 12px 40px rgba(15,23,42,.08); margin-bottom: 18px; }
        h1 { margin: 0 0 8px; color: var(--navy); font-size: 1.8rem; }
        p.lead { color:#475569; margin-top:0; }
        .check { display:flex; gap:12px; padding:10px 0; border-bottom:1px solid #e2e8f0; }
        .check:last-child { border-bottom:0; }
        .dot { width:12px; height:12px; border-radius:99px; margin-top:6px; flex:0 0 12px; }
        .dot.ok { background: var(--ok); }
        .dot.bad { background: var(--bad); }
        label { display:block; font-size:.85rem; font-weight:600; margin: 12px 0 6px; }
        input, select { width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:10px; font-size:1rem; }
        .grid { display:grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        @media (max-width: 700px) { .grid { grid-template-columns: 1fr; } }
        .modes { display:grid; gap:10px; }
        .mode { border:1px solid #cbd5e1; border-radius:12px; padding:12px 14px; }
        .mode input { width:auto; margin-right:8px; }
        .btn { display:inline-block; background: var(--navy); color:#fff; border:0; border-radius:12px; padding:12px 18px; font-weight:700; cursor:pointer; }
        .btn[disabled] { opacity:.5; cursor:not-allowed; }
        .alert { padding:12px 14px; border-radius:12px; margin-bottom:14px; }
        .alert.error { background:#fef2f2; color: var(--bad); }
        .alert.ok { background:#ecfdf5; color: var(--ok); }
        .hint { font-size:.85rem; color:#64748b; }
        .admin-box { display:none; }
        body.show-admin .admin-box { display:block; }
    </style>
</head>
<body class="{{ old('mode', 'import') === 'create' ? 'show-admin' : '' }}">
<div class="wrap">
    <div class="card">
        <h1>Instalar SAMS</h1>
        <p class="lead">Conecta el sistema con MySQL. Luego el inicio, el login y el panel usan esa misma base de datos y los estilos ya compilados.</p>

        @if(session('error'))
            <div class="alert error">{{ session('error') }}</div>
        @endif

        @foreach($checks as $check)
            <div class="check">
                <span class="dot {{ $check['ok'] ? 'ok' : 'bad' }}"></span>
                <div>
                    <strong>{{ $check['label'] }}</strong>
                    <div class="hint">{{ $check['detail'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <form method="post" action="{{ url('/instalar') }}" class="card">
        @csrf
        <h2 style="margin-top:0">1. Dirección del sitio</h2>
        <label for="app_url">URL pública</label>
        <input id="app_url" name="app_url" value="{{ old('app_url', $appUrl) }}" required>
        <p class="hint">Debe coincidir con lo que ves en el navegador, por ejemplo https://temporal.preventionworld.org</p>

        <h2>2. Base de datos MySQL</h2>
        <div class="grid">
            <div>
                <label for="db_host">Servidor</label>
                <input id="db_host" name="db_host" value="{{ old('db_host', '127.0.0.1') }}" required>
            </div>
            <div>
                <label for="db_port">Puerto</label>
                <input id="db_port" name="db_port" value="{{ old('db_port', '3306') }}" required>
            </div>
            <div>
                <label for="db_database">Nombre de la base</label>
                <input id="db_database" name="db_database" value="{{ old('db_database') }}" required>
            </div>
            <div>
                <label for="db_username">Usuario</label>
                <input id="db_username" name="db_username" value="{{ old('db_username') }}" required>
            </div>
        </div>
        <label for="db_password">Contraseña</label>
        <input id="db_password" name="db_password" type="password" value="{{ old('db_password') }}">

        <h2>3. Cómo cargar los datos</h2>
        <div class="modes">
            <label class="mode">
                <input type="radio" name="mode" value="import" {{ old('mode', 'import') === 'import' ? 'checked' : '' }} onchange="document.body.classList.remove('show-admin')">
                Ya importé el SQL en phpMyAdmin (usuarios y equipos existentes)
            </label>
            <label class="mode">
                <input type="radio" name="mode" value="create" {{ old('mode') === 'create' ? 'checked' : '' }} onchange="document.body.classList.add('show-admin')">
                Base vacía: crear tablas y un administrador nuevo
            </label>
        </div>

        <div class="admin-box">
            <h2>Administrador inicial</h2>
            <label for="admin_name">Nombre</label>
            <input id="admin_name" name="admin_name" value="{{ old('admin_name', 'Super Admin') }}">
            <label for="admin_email">Correo</label>
            <input id="admin_email" name="admin_email" type="email" value="{{ old('admin_email') }}">
            <label for="admin_password">Contraseña (mínimo 10 caracteres, mayúscula y número)</label>
            <input id="admin_password" name="admin_password" type="password">
        </div>

        <p style="margin-top:22px">
            <button class="btn" type="submit" {{ $ready ? '' : 'disabled' }}>Conectar y entrar al sistema</button>
        </p>
        <p class="hint">Esto guarda <code>.env</code>, prueba MySQL y deja sesiones/caché en archivos para que funcione en hosting compartido.</p>
    </form>
</div>
</body>
</html>
