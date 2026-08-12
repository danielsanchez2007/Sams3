{{-- Contenido compartido: HTML descargable y PDF (Dompdf). $isPdf = true en PDF. --}}
@php
    $isPdf = $isPdf ?? false;
    $pdfSection = $pdfSection ?? 'all';
    $routeRows = $routeRows ?? [];
    $routeTotalListed = count($routeRows);
@endphp

@if($isPdf && in_array($pdfSection, ['all', 'pre_routes'], true))
<div class="pdf-cover">
    <div class="pdf-cover-kicker">Documentación técnica</div>
    <div class="pdf-cover-title">{{ $appName }}</div>
    <div class="pdf-cover-sub">Manual técnico del proyecto Sams3 (SAMS)</div>
    <div class="pdf-cover-meta">
        Generado: {{ $generatedHuman }}<br/>
        URL de aplicación (referencia): {{ $appUrl }}<br/>
        Laravel {{ $laravelVersion }} · PHP {{ $phpVersion }}
    </div>
</div>
<div class="pdf-break"></div>
@endif

@if(!$isPdf || in_array($pdfSection, ['all', 'pre_routes'], true))
<header class="doc-head">
    <h1>Manual técnico — SAMS (Sams3)</h1>
    <p class="muted">Aplicación: <strong>{{ $appName }}</strong> · ISO-8601: <strong>{{ $generatedAt }}</strong>@if(!empty($generatedHuman)) · Legible: <strong>{{ $generatedHuman }}</strong>@endif</p>
    <p class="muted">Laravel <strong>{{ $laravelVersion }}</strong> · PHP <strong>{{ $phpVersion }}</strong> · Migraciones PHP en <code>database/migrations</code>: <strong>{{ $migrationCount ?? '—' }}</strong></p>
    @if($isPdf)
        <p><strong>Formato:</strong> PDF generado en servidor con <strong>Dompdf</strong> (<code>barryvdh/laravel-dompdf</code>). Fuentes del motor (p. ej. DejaVu Sans). Las páginas de la <strong>tabla de rutas</strong> pueden ir en <strong>horizontal</strong> y el resto en vertical; los fragmentos se unen con FPDI. Los enlaces del índice solo funcionan dentro del mismo archivo PDF parcial.</p>
    @else
        <p><span class="tag">HTML</span> Archivo HTML. PDF equivalente: ruta <code>/admin/docs/manual-tecnico.pdf</code> (mismo rol de contenido, solo administrador global).</p>
    @endif
</header>

<h2 id="sec-intro">Introducción</h2>
<p>Este manual describe la arquitectura, dependencias, configuración, superficie HTTP y buenas prácticas del <strong>monolito Laravel</strong> SAMS (código interno Sams3) usado por Prevention World. Está pensado para <strong>administradores de sistema, desarrolladores y soporte nivel 2</strong>: no sustituye políticas de seguridad de la organización ni credenciales reales (no se incluyen secretos).</p>
<p>El documento se <strong>regenera en cada descarga</strong>: las tablas de dependencias reflejan <code>composer.lock</code> y <code>package-lock.json</code> del momento; el listado de rutas proviene de <code>php artisan route:list --json</code> (hasta 320 entradas, ordenadas por URI, excluyendo rutas Dusk y health <code>up</code>); los inventarios de <code>app/Models</code>, controladores, middleware, servicios y comandos listan archivos presentes en disco.</p>
<p><strong>Alcance funcional resumido:</strong> multi-empresa con contexto de sesión; inventario de equipos y prefijos de código; formatos PDF/HTML; exportaciones; asignaciones con firmas y flujos de devolución; préstamos temporales; inspecciones; hoja de vida; sugerencias; modo oficina; panel administrativo con estadísticas (incluye datos agregados para gráficos); asistente opcional con proveedores de IA externos y reportes SQL en vivo cuando hay API keys configuradas.</p>

<h2 id="sec-indice">Tabla de contenidos</h2>
<div class="toc-wrap">
    <table class="toc-table">
        <tbody>
        <tr><td><a href="#sec-intro">Introducción</a></td><td class="toc-num"></td></tr>
        <tr><td><a href="#sec-1">1. Resumen ejecutivo</a></td><td class="toc-num">1</td></tr>
        <tr><td><a href="#sec-2">2. Variables de entorno (.env)</a></td><td class="toc-num">2</td></tr>
        <tr><td><a href="#sec-3">3. Archivos de configuración (config/)</a></td><td class="toc-num">3</td></tr>
        <tr><td><a href="#sec-4">4. Dependencias PHP — producción (composer)</a></td><td class="toc-num">4</td></tr>
        <tr><td><a href="#sec-5">5. Dependencias PHP — desarrollo (composer)</a></td><td class="toc-num">5</td></tr>
        <tr><td><a href="#sec-6">6. Dependencias JavaScript (npm)</a></td><td class="toc-num">6</td></tr>
        <tr><td><a href="#sec-7">7. CDN y recursos externos</a></td><td class="toc-num">7</td></tr>
        <tr><td><a href="#sec-8">8. Estructura de carpetas y capas</a></td><td class="toc-num">8</td></tr>
        <tr><td><a href="#sec-9">9. Rutas HTTP (listado desde Artisan)</a></td><td class="toc-num">9</td></tr>
        <tr><td><a href="#sec-10">10. Middleware y flujo de sesión</a></td><td class="toc-num">10</td></tr>
        <tr><td><a href="#sec-11">11. Prefijos de código de equipos</a></td><td class="toc-num">11</td></tr>
        <tr><td><a href="#sec-12">12. Límites de subida y PHP</a></td><td class="toc-num">12</td></tr>
        <tr><td><a href="#sec-13">13. PDF en el proyecto (Dompdf)</a></td><td class="toc-num">13</td></tr>
        <tr><td><a href="#sec-14">14. Excel (Maatwebsite)</a></td><td class="toc-num">14</td></tr>
        <tr><td><a href="#sec-15">15. Asistente (GeminiChatController)</a></td><td class="toc-num">15</td></tr>
        <tr><td><a href="#sec-16">16. Controladores destacados (mapa rápido)</a></td><td class="toc-num">16</td></tr>
        <tr><td><a href="#sec-17">17. Inventario de código fuente</a></td><td class="toc-num">17</td></tr>
        <tr><td><a href="#sec-18">18. Pruebas (PHPUnit)</a></td><td class="toc-num">18</td></tr>
        <tr><td><a href="#sec-19">19. Despliegue y rendimiento</a></td><td class="toc-num">19</td></tr>
        <tr><td><a href="#sec-20">20. Descarga del manual (administrador global)</a></td><td class="toc-num">20</td></tr>
        </tbody>
    </table>
</div>

<h2 id="sec-1">1. Resumen ejecutivo</h2>
<p>SAMS (Sams3) es una aplicación web <strong>monolítica Laravel</strong> para Prevention World: multi-empresa, inventario de equipos con códigos por prefijos, formatos PDF/HTML, exportaciones, asignaciones con firmas, préstamos temporales, inspecciones, hoja de vida y asistente opcional (IA externa + datos en vivo vía SQL cuando corresponde).</p>
<ul>
    <li><strong>Runtime:</strong> PHP 8.2+, extensiones habituales <code>pdo_mysql</code>, OpenSSL, JSON, Mbstring, GD, etc.</li>
    <li><strong>Framework:</strong> Laravel — rutas HTTP, middleware, Eloquent, validación, colas opcionales, caché de configuración/vistas.</li>
    <li><strong>Autenticación UI:</strong> paquete <code>laravel/ui</code> (<code>Auth::routes()</code>).</li>
    <li><strong>PDF:</strong> <code>barryvdh/laravel-dompdf</code> — en el código se usa a menudo <code>Pdf::setOptions(['isHtml5ParserEnabled' =&gt; true, 'isRemoteEnabled' =&gt; true])</code> para inspecciones (imágenes remotas); el manual PDF usa opciones conservadoras sin red para mayor estabilidad.</li>
    <li><strong>Manipulación PDF avanzada:</strong> <code>mikehaertl/php-pdftk</code> (requiere binario <code>pdftk</code> en el servidor si se usa).</li>
    <li><strong>Excel:</strong> <code>maatwebsite/excel</code> (PhpSpreadsheet).</li>
    <li><strong>Front build:</strong> Vite + plugin Laravel, Sass, Bootstrap 5, Tailwind (también Tailwind CDN en varios layouts).</li>
    <li><strong>Gráficos / iconos (npm):</strong> Chart.js, Lucide.</li>
</ul>

<h2 id="sec-2">2. Variables de entorno (.env) — referencia de claves</h2>
<p class="muted">Este documento no incluye valores secretos; solo nombres de variables frecuentes.</p>
<ul>
    <li><code>APP_NAME</code>, <code>APP_URL</code>, <code>APP_ENV</code>, <code>APP_DEBUG</code>, <code>APP_KEY</code></li>
    <li><code>DB_CONNECTION</code>, <code>DB_HOST</code>, <code>DB_PORT</code>, <code>DB_DATABASE</code>, <code>DB_USERNAME</code>, <code>DB_PASSWORD</code></li>
    <li><code>FILESYSTEM_DISK</code>, disco <code>public</code> → <code>storage/app/public</code> y enlace <code>php artisan storage:link</code></li>
    <li><code>SESSION_DRIVER</code>, <code>CACHE_STORE</code>, <code>QUEUE_CONNECTION</code>, <code>MAIL_*</code></li>
    <li><code>GEMINI_API_KEY</code>, <code>GROQ_API_KEY</code>, <code>OPENROUTER_API_KEY</code>, <code>AI_CHAT_PROVIDERS</code>, <code>AI_CHAT_MAX_TOTAL_SECONDS</code>, <code>AI_CHAT_PER_PROVIDER_TIMEOUT</code></li>
    <li><code>GEMINI_MODEL</code> (opcional), <code>GOOGLE_MAPS_API_KEY</code></li>
    <li><code>SAMS_ALLOW_REGISTRATION</code>, <code>SAMS_LIGHTWEIGHT_UI</code>, <code>SAMS_CODIGOS_SYNC_TTL</code></li>
</ul>

<h2 id="sec-3">3. Archivos de configuración relevantes (config/)</h2>
<p>Además de los archivos PHP listados abajo (presentes en el proyecto al generar el manual), estos son los más citados en soporte:</p>
<ul>
    <li><code>config/app.php</code> — nombre, entorno, debug, URL, locale.</li>
    <li><code>config/database.php</code> — conexiones MySQL/SQLite.</li>
    <li><code>config/filesystems.php</code> — discos <code>local</code>, <code>public</code>, S3 opcional.</li>
    <li><code>config/auth.php</code> — guards, providers.</li>
    <li><code>config/ai-chat.php</code> — proveedores IA y timeouts.</li>
    <li><code>config/sams.php</code> — registro, UI ligera, TTL códigos.</li>
    <li><code>config/sams-assistant.php</code> — texto de conocimiento del programa para el asistente.</li>
    <li><code>config/gemini.php</code> — clave/modelo legacy si aplica.</li>
</ul>
<h3>Archivos <code>*.php</code> en <code>config/</code> (inventario)</h3>
@if(!empty($configFiles))
    <pre>@foreach($configFiles as $cfg){{ $cfg }}
@endforeach</pre>
@else
    <p class="muted">No se pudo listar <code>config/</code>.</p>
@endif

<h2 id="sec-4">4. Dependencias PHP — producción (composer.json + versiones en composer.lock)</h2>
<table class="data">
    <thead><tr><th>Paquete</th><th>Restricción</th><th>Versión instalada (lock)</th></tr></thead>
    <tbody>
    @forelse($composerRows as $row)
        <tr>
            <td><code>{{ $row['name'] }}</code></td>
            <td><code>{{ $row['constraint'] }}</code></td>
            <td>{{ $row['installed'] }}</td>
        </tr>
    @empty
        <tr><td colspan="3">No hay datos de Composer (falta <code>composer.json</code> o <code>composer.lock</code>).</td></tr>
    @endforelse
    </tbody>
</table>

<h2 id="sec-5">5. Dependencias PHP — desarrollo (require-dev)</h2>
<table class="data">
    <thead><tr><th>Paquete</th><th>Restricción</th><th>Versión instalada (lock)</th></tr></thead>
    <tbody>
    @forelse($composerDevRows ?? [] as $row)
        <tr>
            <td><code>{{ $row['name'] }}</code></td>
            <td><code>{{ $row['constraint'] }}</code></td>
            <td>{{ $row['installed'] }}</td>
        </tr>
    @empty
        <tr><td colspan="3">Sin <code>require-dev</code> o sin lock legible.</td></tr>
    @endforelse
    </tbody>
</table>

<h2 id="sec-6">6. Dependencias JavaScript (package.json + package-lock.json)</h2>
<table class="data">
    <thead><tr><th>Paquete</th><th>Tipo</th><th>Rango</th><th>Lock</th></tr></thead>
    <tbody>
    @foreach($npmRows as $row)
        <tr>
            <td><code>{{ $row['name'] }}</code></td>
            <td>{{ $row['kind'] }}</td>
            <td><code>{{ $row['range'] }}</code></td>
            <td><code>{{ $row['installed'] }}</code></td>
        </tr>
    @endforeach
    </tbody>
</table>
<p><code>vite.config.js</code>: plugin <code>laravel-vite-plugin</code>, entradas <code>resources/sass/app.scss</code> y <code>resources/js/app.js</code>.</p>

<h2 id="sec-7">7. CDN y recursos externos (no van en el bundle)</h2>
<ul>
    <li><strong>Tailwind CDN:</strong> <code>cdn.tailwindcss.com</code> — <code>layouts/admin-layout.blade.php</code>, landing <code>resources/views/dashboard.blade.php</code>.</li>
    <li><strong>Lucide CDN:</strong> <code>unpkg.com/lucide</code> en layouts/vistas.</li>
    <li><strong>Chart.js CDN:</strong> jsDelivr en algunas vistas admin con gráficos.</li>
    <li><strong>Google Fonts / Bunny:</strong> CSS remoto en vistas con tipografía custom.</li>
    <li><strong>Particles.js:</strong> landing pública.</li>
</ul>
<p class="muted">Dompdf: con <code>isRemoteEnabled</code> en false (manual PDF) no se cargan esas fuentes remotas; el PDF del manual es autocontenido.</p>

<h2 id="sec-8">8. Estructura de carpetas y capas</h2>
<pre>app/Http/Controllers   # Entrada HTTP por módulo
app/Http/Middleware    # EnsureProfileComplete, EnsurePasswordChanged, PreventAuthPageCache, etc.
app/Models             # Eloquent + traits (p. ej. BelongsToEmpresa, SoftDeletes)
app/Services           # EmpresaContext, AiChat, SamsLiveDataReporter, VistaOficina, etc.
bootstrap/app.php
config/
database/migrations, seeders, factories
resources/views, sass, js
routes/web.php
public/                 # index.php, assets build, storage symlink</pre>
@endif

@if(in_array($pdfSection, ['all', 'routes'], true))
@if($isPdf && $pdfSection === 'routes')
<p class="muted" style="margin-bottom:8px;"><strong>Manual técnico SAMS</strong> — fragmento en <strong>horizontal</strong> para la tabla ancha de rutas (sección 9).</p>
@endif

<h2 id="sec-9">9. Rutas HTTP (listado desde Artisan)</h2>
<p>Generado con <code>php artisan route:list --json</code>. Se excluyen rutas cuya URI comienza por <code>_dusk/</code> y la ruta de health <code>up</code>. Se muestran hasta <strong>320</strong> filas ordenadas por URI. Filas en este PDF/HTML: <strong>{{ $routeTotalListed }}</strong>.</p>
@if($routeTotalListed === 0)
    <p class="muted">No se obtuvieron rutas (error al ejecutar Artisan o salida no JSON). Use en consola: <code>php artisan route:list</code>.</p>
@else
<table class="data data-routes">
    <thead><tr><th>Métodos</th><th>URI</th><th>Nombre</th><th>Acción</th></tr></thead>
    <tbody>
    @foreach($routeRows as $r)
        <tr>
            <td><code>{{ $r['methods'] }}</code></td>
            <td><code>{{ $r['uri'] }}</code></td>
            <td>@if(($r['name'] ?? '') !== '' && $r['name'] !== '—')<code>{{ $r['name'] }}</code>@else—@endif</td>
            <td class="col-action">{{ $r['action'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
@endif
<p class="muted">Definiciones principales en <code>routes/web.php</code>. El panel autenticado suele exigir <code>auth</code>, <code>password.must_change</code> y <code>profile.complete</code> salvo excepciones en middleware.</p>
@endif

@if(in_array($pdfSection, ['all', 'post_routes'], true))
@if($isPdf && $pdfSection === 'post_routes')
<p class="muted" style="margin-bottom:10px;"><strong>Manual técnico SAMS</strong> — continuación (secciones 10–20).</p>
@endif

<h2 id="sec-10">10. Middleware y flujo de sesión</h2>
<ul>
    <li><strong>web:</strong> sesión, cookies, CSRF en formularios; API del asistente usa cabecera <code>X-CSRF-TOKEN</code> + JSON.</li>
    <li><strong>EnsurePasswordChanged:</strong> <code>must_change_password</code> → flujo contraseña segura.</li>
    <li><strong>EnsureProfileComplete:</strong> foto, firma, tipo y número de documento; excepciones: perfil, logout, contraseña segura, descarga del manual técnico.</li>
    <li><strong>EmpresaContext:</strong> empresa activa en sesión; afecta queries y trait <code>BelongsToEmpresa</code>.</li>
</ul>

<h2 id="sec-11">11. Prefijos de código de equipos</h2>
<ul>
    <li><code>IN-</code> — Inventario</li>
    <li><code>DB-</code> — Baja</li>
    <li><code>AUD-</code> — Auditoría</li>
    <li><code>MD-</code> — Material didáctico</li>
</ul>

<h2 id="sec-12">12. Límites de subida (validación Laravel, max en KB)</h2>
<ul>
    <li><strong>Empresa — logos y foto:</strong> <code>image|max:4096</code> (~4 MB por archivo).</li>
    <li><strong>Equipo — evidencias / imágenes:</strong> <code>mimes:jpg,jpeg,png,webp|max:5120</code> (~5 MB por archivo) en <code>EquipoInventarioController</code>.</li>
</ul>
<table>
    <tbody>
        <tr><th>upload_max_filesize</th><td>{{ $phpLimits['upload_max_filesize'] }}</td></tr>
        <tr><th>post_max_size</th><td>{{ $phpLimits['post_max_size'] }}</td></tr>
        <tr><th>max_execution_time</th><td>{{ $phpLimits['max_execution_time'] }}</td></tr>
        <tr><th>memory_limit</th><td>{{ $phpLimits['memory_limit'] }}</td></tr>
    </tbody>
</table>

<h2 id="sec-13">13. PDF en el proyecto (Dompdf) — límites y buenas prácticas</h2>
<ul>
    <li><strong>CSS:</strong> preferir tablas, márgenes simples, evitar flex/grid complejos en plantillas PDF críticas.</li>
    <li><strong>Imágenes:</strong> rutas locales o base64; <code>isRemoteEnabled</code> permite URLs externas (p. ej. firmas en inspección) pero depende de red y tiempo.</li>
    <li><strong>Tamaño / tiempo:</strong> HTML muy largo o muchas imágenes aumentan RAM y CPU — subir <code>memory_limit</code> o paginar documentos.</li>
    <li><strong>pdftk:</strong> solo donde se invoque; validar que el binario exista en producción.</li>
</ul>

<h2 id="sec-14">14. Excel (Maatwebsite)</h2>
<p>Import/export XLSX/CSV. Para volúmenes grandes usar colas (<code>ShouldQueue</code>) y chunking en lecturas para no agotar memoria.</p>

<h2 id="sec-15">15. Asistente (GeminiChatController)</h2>
<ul>
    <li><strong>GET /asistente</strong> — vista <code>admin/gemini/chat.blade.php</code>.</li>
    <li><strong>POST /asistente/chat</strong> — valida mensaje e historial; construye datos en vivo con <code>SamsLiveDataReporter</code>; llama a <code>AiChatService</code> si hay API keys.</li>
    <li><strong>Manual técnico (solo admin global, <code>empresa_id === null</code>):</strong> respuesta JSON con URL de descarga PDF/HTML según petición; el cliente puede disparar descarga automática.</li>
</ul>

<h2 id="sec-16">16. Controladores destacados (mapa rápido)</h2>
<table>
    <thead><tr><th>Área</th><th>Controlador(es)</th></tr></thead>
    <tbody>
        <tr><td>Panel / stats</td><td><code>AdminController</code>, <code>DashboardController</code></td></tr>
        <tr><td>Usuarios / roles / metadatos</td><td><code>UserController</code>, <code>RoleController</code>, <code>CargoController</code>, <code>GrupoController</code>, <code>FabricanteController</code></td></tr>
        <tr><td>Empresa / sedes / bodegas</td><td><code>EmpresaManagementController</code></td></tr>
        <tr><td>Inventario y anexos</td><td><code>EquipoInventarioController</code>, <code>EquipoManagementController</code>, <code>EquiposBajaController</code>, <code>EquiposAuditoriaController</code>, <code>MaterialDidacticoController</code>, <code>TraspasoController</code></td></tr>
        <tr><td>Formatos / HV / inspección / export</td><td><code>FormatosController</code>, <code>HojaVidaController</code>, <code>InspeccionController</code>, <code>ExportarController</code></td></tr>
        <tr><td>Asignar / préstamos</td><td><code>AsignarController</code>, <code>PrestamoTemporalController</code></td></tr>
        <tr><td>Modo oficina</td><td><code>ModoOficinaController</code></td></tr>
        <tr><td>IA</td><td><code>GeminiChatController</code></td></tr>
        <tr><td>Auth / perfil</td><td><code>LoginController</code>, <code>RegisterController</code>, <code>ForcedPasswordController</code>, <code>ProfileController</code></td></tr>
        <tr><td>Docs</td><td><code>ManualTecnicoController</code></td></tr>
    </tbody>
</table>

<h2 id="sec-17">17. Inventario de código fuente</h2>
<p>Listas generadas desde el árbol del proyecto al compilar el manual (nombres de archivo relativos donde aplica).</p>

<h3>Modelos Eloquent (<code>app/Models</code>)</h3>
@if(!empty($modelFiles))
    <pre>@foreach($modelFiles as $m){{ $m }}
@endforeach</pre>
@else
    <p class="muted">Sin archivos listados.</p>
@endif

<h3>Controladores (<code>app/Http/Controllers</code>)</h3>
@if(!empty($controllerFiles))
    <pre>@foreach($controllerFiles as $c){{ $c }}
@endforeach</pre>
@else
    <p class="muted">Sin archivos listados.</p>
@endif

<h3>Middleware (<code>app/Http/Middleware</code>)</h3>
@if(!empty($middlewareFiles))
    <pre>@foreach($middlewareFiles as $mw){{ $mw }}
@endforeach</pre>
@else
    <p class="muted">Sin archivos listados.</p>
@endif

<h3>Servicios (<code>app/Services</code>, rutas relativas)</h3>
@if(!empty($serviceFiles))
    <pre>@foreach($serviceFiles as $s){{ $s }}
@endforeach</pre>
@else
    <p class="muted">Sin archivos listados.</p>
@endif

<h3>Comandos Artisan (<code>app/Console/Commands</code>)</h3>
@if(!empty($consoleCommands))
    <pre>@foreach($consoleCommands as $cmd){{ $cmd }}
@endforeach</pre>
@else
    <p class="muted">Sin archivos listados.</p>
@endif

<h2 id="sec-18">18. Pruebas (PHPUnit)</h2>
<ul>
    <li><code>php artisan test</code> — <code>tests/Unit</code>, <code>tests/Feature</code>.</li>
    <li><code>phpunit.xml</code> — variables de entorno de testing; en Windows/Laragon usar MySQL (<code>pdo_mysql</code>) si SQLite no está habilitado.</li>
</ul>

<h2 id="sec-19">19. Despliegue y rendimiento</h2>
<ul>
    <li><code>composer install --no-dev --optimize-autoloader</code></li>
    <li><code>npm ci</code> y <code>npm run build</code></li>
    <li><code>php artisan config:cache route:cache view:cache</code></li>
    <li><code>APP_DEBUG=false</code> en producción</li>
    <li>Panel admin: evitar volcar datasets enormes en Blade; preferir endpoints JSON agregados para gráficos (p. ej. equipos del dashboard).</li>
</ul>

<h2 id="sec-20">20. Descarga del manual (solo administrador global)</h2>
<ul>
    <li>Condición: <code>auth()->user()->empresa_id === null</code>.</li>
    <li>HTML: ruta nombrada <code>admin.docs.manual-tecnico</code>.</li>
    <li>PDF: ruta nombrada <code>admin.docs.manual-tecnico-pdf</code>.</li>
    <li>En el asistente, el historial reciente del usuario ayuda a interpretar frases como «sí, en PDF» después de pedir el manual.</li>
</ul>

<footer class="muted" style="margin-top:2rem;border-top:1px solid #e2e8f0;padding-top:0.75rem;">
    Documento generado por el sistema SAMS. Versionar junto al código fuente. Para el listado completo de rutas sin límite, ejecutar <code>php artisan route:list</code> en el entorno correspondiente.
</footer>
@endif
