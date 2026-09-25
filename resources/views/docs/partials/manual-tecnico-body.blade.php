{{-- Manual técnico SAMS — contenido compartido HTML/PDF. $isPdf = true en PDF. --}}
@php
    $isPdf = $isPdf ?? false;
    $pdfSection = $pdfSection ?? 'all';
    $routeRows = $routeRows ?? [];
    $routeTotalListed = count($routeRows);
    $docId = $documentId ?? ('SAMS-MT-' . now()->format('Y'));
    $docVersion = $documentVersion ?? '1.0.0';
    $docClass = $documentClassification ?? 'Uso interno';
    $organization = $organization ?? 'Prevention World';
@endphp

@if($isPdf && in_array($pdfSection, ['all', 'pre_routes'], true))
<div class="pdf-cover">
    <div class="pdf-cover-kicker">Documentación técnica oficial</div>
    <div class="pdf-cover-title">{{ $appName }}</div>
    <div class="pdf-cover-sub">Manual técnico del sistema SAMS (Sams3)</div>
    <div class="pdf-cover-meta">
        {{ $organization }}<br/>
        ID documento: {{ $docId }} · Versión {{ $docVersion }}<br/>
        Clasificación: {{ $docClass }}<br/>
        Generado: {{ $generatedHuman }}<br/>
        Laravel {{ $laravelVersion }} · PHP {{ $phpVersion }}
    </div>
</div>
<div class="pdf-break"></div>
@endif

@if(!$isPdf)
<div class="doc-cover">
    <div class="doc-cover-kicker">Documentación técnica oficial</div>
    <div class="doc-cover-title">{{ $appName }}</div>
    <div class="doc-cover-sub">Manual técnico del sistema SAMS (Sams3)</div>
    <div class="doc-cover-meta">
        {{ $organization }} · ID {{ $docId }} · Versión {{ $docVersion }}<br/>
        Generado: {{ $generatedHuman }}
    </div>
</div>
@endif

@if(!$isPdf || in_array($pdfSection, ['all', 'pre_routes'], true))

<h2 id="sec-control">Control del documento</h2>
<table class="doc-control">
    <tbody>
        <tr><th>Identificador</th><td><code>{{ $docId }}</code></td></tr>
        <tr><th>Título</th><td>Manual técnico — SAMS (Sams3)</td></tr>
        <tr><th>Versión</th><td>{{ $docVersion }}</td></tr>
        <tr><th>Clasificación</th><td>{{ $docClass }}</td></tr>
        <tr><th>Organización</th><td>{{ $organization }}</td></tr>
        <tr><th>Aplicación</th><td>{{ $appName }} — {{ $appUrl }}</td></tr>
        <tr><th>Fecha de generación</th><td>{{ $generatedHuman }} ({{ $generatedAt }})</td></tr>
        <tr><th>Stack</th><td>Laravel {{ $laravelVersion }} · PHP {{ $phpVersion }} · {{ $migrationCount ?? '—' }} migraciones</td></tr>
        <tr><th>Formato</th><td>@if($isPdf) PDF (Dompdf + FPDI) @else HTML descargable @endif — regenerado en cada descarga</td></tr>
        <tr><th>Audiencia</th><td>Administradores de sistema, desarrolladores, soporte N2/N3</td></tr>
    </tbody>
</table>
<div class="notice"><strong>Nota de confidencialidad:</strong> este documento describe arquitectura y configuración. No incluye credenciales, claves API ni datos personales de usuarios finales.</div>

<header class="doc-head">
    <h1>Manual técnico — SAMS (Sams3)</h1>
    <p class="muted">
        <span class="tag">SAMS</span>
        Sistema de Administración y Mantenimiento de equipos de seguridad industrial.
        Monolito Laravel multi-empresa para Prevention World.
    </p>
    @if(!$isPdf)
        <p class="muted">PDF equivalente: <code>/admin/docs/manual-tecnico.pdf</code> (solo administrador global).</p>
    @endif
</header>

<h2 id="sec-indice">Tabla de contenidos</h2>
<div class="toc-wrap">
    <table class="toc-table">
        <tbody>
        <tr><td><a href="#sec-control">Control del documento</a></td><td class="toc-num"></td></tr>
        <tr><td><a href="#sec-1">1. Introducción y propósito</a></td><td class="toc-num">1</td></tr>
        <tr><td><a href="#sec-2">2. Alcance y audiencia</a></td><td class="toc-num">2</td></tr>
        <tr><td><a href="#sec-3">3. Definiciones y abreviaturas</a></td><td class="toc-num">3</td></tr>
        <tr><td><a href="#sec-4">4. Resumen ejecutivo</a></td><td class="toc-num">4</td></tr>
        <tr><td><a href="#sec-5">5. Arquitectura del sistema</a></td><td class="toc-num">5</td></tr>
        <tr><td><a href="#sec-6">6. Requisitos e instalación</a></td><td class="toc-num">6</td></tr>
        <tr><td><a href="#sec-7">7. Variables de entorno</a></td><td class="toc-num">7</td></tr>
        <tr><td><a href="#sec-8">8. Configuración (config/)</a></td><td class="toc-num">8</td></tr>
        <tr><td><a href="#sec-9">9. Rutas HTTP</a></td><td class="toc-num">9</td></tr>
        <tr><td><a href="#sec-10">10. Seguridad</a></td><td class="toc-num">10</td></tr>
        <tr><td><a href="#sec-11">11. Multi-empresa y autorización</a></td><td class="toc-num">11</td></tr>
        <tr><td><a href="#sec-12">12. Módulos funcionales</a></td><td class="toc-num">12</td></tr>
        <tr><td><a href="#sec-13">13. Modelo de datos</a></td><td class="toc-num">13</td></tr>
        <tr><td><a href="#sec-14">14. Flujos de negocio</a></td><td class="toc-num">14</td></tr>
        <tr><td><a href="#sec-15">15. Middleware y sesión</a></td><td class="toc-num">15</td></tr>
        <tr><td><a href="#sec-16">16. Prefijos de código</a></td><td class="toc-num">16</td></tr>
        <tr><td><a href="#sec-17">17. Archivos y límites PHP</a></td><td class="toc-num">17</td></tr>
        <tr><td><a href="#sec-18">18. Generación PDF</a></td><td class="toc-num">18</td></tr>
        <tr><td><a href="#sec-19">19. Excel (Maatwebsite)</a></td><td class="toc-num">19</td></tr>
        <tr><td><a href="#sec-21">21. Dependencias PHP (producción)</a></td><td class="toc-num">21</td></tr>
        <tr><td><a href="#sec-22">22. Dependencias PHP (desarrollo)</a></td><td class="toc-num">22</td></tr>
        <tr><td><a href="#sec-23">23. Dependencias JavaScript</a></td><td class="toc-num">23</td></tr>
        <tr><td><a href="#sec-24">24. CDN y recursos externos</a></td><td class="toc-num">24</td></tr>
        <tr><td><a href="#sec-25">25. Estructura de carpetas</a></td><td class="toc-num">25</td></tr>
        <tr><td><a href="#sec-26">26. Mapa de controladores</a></td><td class="toc-num">26</td></tr>
        <tr><td><a href="#sec-27">27. Inventario de código</a></td><td class="toc-num">27</td></tr>
        <tr><td><a href="#sec-28">28. Pruebas</a></td><td class="toc-num">28</td></tr>
        <tr><td><a href="#sec-29">29. Despliegue</a></td><td class="toc-num">29</td></tr>
        <tr><td><a href="#sec-30">30. Operaciones y mantenimiento</a></td><td class="toc-num">30</td></tr>
        <tr><td><a href="#sec-31">31. Resolución de problemas</a></td><td class="toc-num">31</td></tr>
        <tr><td><a href="#sec-32">32. Descarga del manual</a></td><td class="toc-num">32</td></tr>
        </tbody>
    </table>
</div>

<h2 id="sec-1">1. Introducción y propósito</h2>
<p><strong>SAMS</strong> (código interno <strong>Sams3</strong>) es la plataforma web de Prevention World para la gestión integral del ciclo de vida de equipos de protección contra caídas y material relacionado. Este manual técnico documenta la arquitectura, configuración, seguridad, módulos, datos y procedimientos operativos del sistema.</p>
<p><strong>Propósito del documento:</strong></p>
<ul>
    <li>Servir como referencia única para instalación, configuración y mantenimiento.</li>
    <li>Describir la arquitectura y las decisiones técnicas relevantes.</li>
    <li>Facilitar la incorporación de nuevos desarrolladores y personal de soporte.</li>
    <li>Documentar superficie HTTP, dependencias y límites operativos.</li>
</ul>
<p>El documento se <strong>regenera automáticamente</strong> en cada descarga: dependencias desde <code>composer.lock</code> y <code>package-lock.json</code>; rutas desde <code>php artisan route:list --json</code> (máx. 320 entradas); inventarios de archivos desde disco.</p>

<h2 id="sec-2">2. Alcance y audiencia</h2>
<h3>2.1 Alcance</h3>
<p>Incluye: aplicación monolítica Laravel, base de datos MySQL, almacenamiento en disco <code>public</code>, generación PDF/Excel, panel administrativo, multi-empresa, roles y permisos.</p>
<p>Excluye: políticas corporativas de Prevention World, procedimientos de campo (inspección física), infraestructura de red del cliente y gestión de credenciales en producción.</p>
<h3>2.2 Audiencia</h3>
<table>
    <thead><tr><th>Rol</th><th>Uso del manual</th></tr></thead>
    <tbody>
        <tr><td>Administrador global SAMS</td><td>Despliegue, empresas, manual técnico, configuración avanzada</td></tr>
        <tr><td>Desarrollador</td><td>Arquitectura, rutas, modelos, servicios, pruebas</td></tr>
        <tr><td>Soporte N2/N3</td><td>Troubleshooting, límites PHP, logs, cachés</td></tr>
        <tr><td>Administrador de empresa</td><td>Secciones 11–14 (módulos, flujos, permisos)</td></tr>
    </tbody>
</table>

<h2 id="sec-3">3. Definiciones y abreviaturas</h2>
<table>
    <thead><tr><th>Término</th><th>Definición</th></tr></thead>
    <tbody>
        <tr><td><strong>SAMS</strong></td><td>Sistema de Administración y Mantenimiento (nombre comercial del producto).</td></tr>
        <tr><td><strong>Sams3</strong></td><td>Nombre del repositorio/código fuente del proyecto Laravel.</td></tr>
        <tr><td><strong>Prevention World</strong></td><td>Empresa matriz; contexto multi-tenant principal del sistema.</td></tr>
        <tr><td><strong>Tenant / Empresa</strong></td><td>Organización cliente con datos aislados por <code>empresa_id</code>.</td></tr>
        <tr><td><strong>Admin global</strong></td><td>Usuario con <code>empresa_id = null</code>; acceso transversal a empresas.</td></tr>
        <tr><td><strong>HV</strong></td><td>Hoja de vida del equipo (documento técnico/registro).</td></tr>
        <tr><td><strong>Dompdf</strong></td><td>Motor de renderizado HTML→PDF usado en formatos e inspecciones.</td></tr>
        <tr><td><strong>CSP</strong></td><td>Content Security Policy — política de cabeceras HTTP de seguridad.</td></tr>
        <tr><td><strong>CSRF</strong></td><td>Cross-Site Request Forgery — protección de formularios web Laravel.</td></tr>
    </tbody>
</table>

<h2 id="sec-4">4. Resumen ejecutivo</h2>
<p>SAMS es una aplicación web <strong>monolítica Laravel 12</strong> sobre <strong>PHP 8.2+</strong> y <strong>MySQL</strong>, orientada a:</p>
<ul>
    <li><strong>Inventario multi-empresa</strong> de equipos con códigos únicos por prefijos configurables.</li>
    <li><strong>Formatos</strong> PDF/HTML personalizables por plantillas.</li>
    <li><strong>Inspecciones</strong> periódicas con plantillas por tipo/clase de equipo.</li>
    <li><strong>Hoja de vida</strong> completa por equipo con exportación PDF.</li>
    <li><strong>Asignaciones</strong> a usuarios con firmas digitales y flujos de devolución.</li>
    <li><strong>Préstamos temporales</strong> con revisión y devolución.</li>
    <li><strong>Bajas, auditoría y material didáctico</strong> como submódulos de equipos.</li>
    <li><strong>Gestión organizacional:</strong> sedes, bodegas, oficinas, espacios, cargos, grupos.</li>
    <li><strong>Panel administrativo</strong> con estadísticas y gráficos.</li>
</ul>
<table>
    <thead><tr><th>Componente</th><th>Tecnología</th></tr></thead>
    <tbody>
        <tr><td>Backend</td><td>Laravel 12, Eloquent ORM, sesiones, colas opcionales</td></tr>
        <tr><td>Autenticación UI</td><td><code>laravel/ui</code> — login, registro condicional, recuperación</td></tr>
        <tr><td>Frontend</td><td>Blade, Vite, Sass, Bootstrap 5, Tailwind (CDN + bundle)</td></tr>
        <tr><td>PDF</td><td><code>barryvdh/laravel-dompdf</code>, FPDI para unión de PDFs</td></tr>
        <tr><td>Excel</td><td><code>maatwebsite/excel</code> (PhpSpreadsheet)</td></tr>
        <tr><td>Iconos / gráficos</td><td>Lucide, Chart.js (CDN en vistas admin)</td></tr>
    </tbody>
</table>

<h2 id="sec-5">5. Arquitectura del sistema</h2>
<h3>5.1 Capas lógicas</h3>
<pre>┌─────────────────────────────────────────────────────────┐
│  Presentación: Blade + JS (Vite/Tailwind/Lucide)        │
├─────────────────────────────────────────────────────────┤
│  HTTP: routes/web.php → Controllers → Form Requests     │
├─────────────────────────────────────────────────────────┤
│  Middleware: auth, CSRF, SecurityHeaders, perfil, pwd   │
├─────────────────────────────────────────────────────────┤
│  Dominio: Services (EmpresaContext, Codigos…)           │
│           Policies (UserPolicy, EquipoPolicy)           │
├─────────────────────────────────────────────────────────┤
│  Persistencia: Eloquent Models + MySQL                  │
├─────────────────────────────────────────────────────────┤
│  Archivos: storage/app/public (symlink public/storage)  │
└─────────────────────────────────────────────────────────┘</pre>
<h3>5.2 Patrones aplicados</h3>
<ul>
    <li><strong>MVC clásico Laravel</strong> con separación parcial en Services para lógica transversal.</li>
    <li><strong>Multi-tenancy por columna</strong> (<code>empresa_id</code>) con trait <code>BelongsToEmpresa</code>.</li>
    <li><strong>Autorización por módulo</strong> centralizada en <code>EmpresaModuleAuthorization</code>.</li>
    <li><strong>Contexto de empresa activa</strong> en sesión (<code>EmpresaContext</code>) para admin global.</li>
    <li><strong>Validación de entrada</strong> mediante Form Requests en operaciones críticas de equipos.</li>
    <li><strong>Sanitización HTML</strong> en inspecciones (<code>HtmlSanitizer</code>).</li>
</ul>
<h3>5.3 Diagrama de contexto multi-empresa</h3>
<pre>Usuario global (empresa_id = null)
    ├── Panel matriz / listado empresas
    ├── "Entrar a empresa" → sesión empresa_activa_id
    └── Permisos según rol + módulos empresa activa

Usuario de empresa (empresa_id = N)
    ├── Datos filtrados por empresa_id
    └── Módulos según empresa.modulos (none | view | edit)</pre>

<h2 id="sec-6">6. Requisitos e instalación</h2>
<h3>6.1 Requisitos mínimos</h3>
<table>
    <thead><tr><th>Requisito</th><th>Versión / nota</th></tr></thead>
    <tbody>
        <tr><td>PHP</td><td>8.2 o superior</td></tr>
        <tr><td>Extensiones PHP</td><td>pdo_mysql, openssl, mbstring, json, fileinfo, gd, xml, zip</td></tr>
        <tr><td>MySQL / MariaDB</td><td>5.7+ / 10.3+</td></tr>
        <tr><td>Composer</td><td>2.x</td></tr>
        <tr><td>PHP</td><td>8.2+ (8.3 recomendado)</td></tr>
        <tr><td>Servidor web</td><td>Apache/Nginx apuntando a <code>public/</code></td></tr>
    </tbody>
</table>
<h3>6.2 Instalación (entorno de desarrollo)</h3>
<pre>git clone https://github.com/danielsanchez2007/Sams3.git
cd Sams3
composer install
cp .env.example .env
php artisan key:generate
# Configurar DB_* en .env
php artisan migrate --seed
npm install
npm run build
# No crear php artisan storage:link</pre>
<h3>6.3 Comandos Artisan útiles</h3>
<table>
    <thead><tr><th>Comando</th><th>Descripción</th></tr></thead>
    <tbody>
        <tr><td><code>php artisan migrate</code></td><td>Aplicar migraciones de base de datos</td></tr>
        <tr><td><code>php artisan db:seed</code></td><td>Ejecutar seeders (roles, empresas demo)</td></tr>
        <tr><td><code>php artisan config:cache</code></td><td>Caché de configuración (producción)</td></tr>
        <tr><td><code>php artisan route:list</code></td><td>Listado completo de rutas HTTP</td></tr>
        <tr><td><code>php artisan test</code></td><td>Ejecutar pruebas PHPUnit</td></tr>
    </tbody>
</table>

<h2 id="sec-7">7. Variables de entorno (.env)</h2>
<p class="muted">Solo nombres de variables; nunca incluir valores secretos en documentación.</p>
<table>
    <thead><tr><th>Variable</th><th>Descripción</th></tr></thead>
    <tbody>
        <tr><td><code>APP_NAME</code>, <code>APP_URL</code>, <code>APP_ENV</code>, <code>APP_DEBUG</code>, <code>APP_KEY</code></td><td>Identidad y entorno de la aplicación</td></tr>
        <tr><td><code>DB_*</code></td><td>Conexión MySQL (host, puerto, base, usuario, contraseña)</td></tr>
        <tr><td><code>FILESYSTEM_DISK</code></td><td>Disco por defecto; archivos públicos en <code>public</code></td></tr>
        <tr><td><code>SESSION_*</code>, <code>CACHE_*</code>, <code>QUEUE_*</code></td><td>Sesión, caché y colas</td></tr>
        <tr><td><code>MAIL_*</code></td><td>Envío de correos (recuperación contraseña)</td></tr>
        <tr><td><code>GOOGLE_MAPS_API_KEY</code></td><td>Geocodificación en gestión de empresa</td></tr>
        <tr><td><code>SAMS_ALLOW_REGISTRATION</code></td><td>Registro público (default: false)</td></tr>
        <tr><td><code>SAMS_LIGHTWEIGHT_UI</code></td><td>UI sin partículas/burbujas (default: true)</td></tr>
        <tr><td><code>SAMS_CODIGOS_SYNC_TTL</code></td><td>Segundos entre sync de códigos reutilizables</td></tr>
    </tbody>
</table>

<h2 id="sec-8">8. Configuración (config/)</h2>
<table>
    <thead><tr><th>Archivo</th><th>Responsabilidad</th></tr></thead>
    <tbody>
        <tr><td><code>config/app.php</code></td><td>Nombre, locale, timezone, debug</td></tr>
        <tr><td><code>config/database.php</code></td><td>Conexiones de base de datos</td></tr>
        <tr><td><code>config/filesystems.php</code></td><td>Discos local, public, S3</td></tr>
        <tr><td><code>config/auth.php</code></td><td>Guards y providers de autenticación</td></tr>
        <tr><td><code>config/sams.php</code></td><td>Módulos, logos default, registro, UI ligera, TTL códigos</td></tr>
    </tbody>
</table>
<h3>8.1 Inventario <code>config/*.php</code></h3>
@if(!empty($configFiles))
    <pre>@foreach($configFiles as $cfg){{ $cfg }}
@endforeach</pre>
@else
    <p class="muted">No se pudo listar <code>config/</code>.</p>
@endif

@endif

@if(in_array($pdfSection, ['all', 'routes'], true))
@if($isPdf && $pdfSection === 'routes')
<p class="muted" style="margin-bottom:8px;"><strong>Manual técnico SAMS</strong> — Sección 9: tabla de rutas (orientación horizontal).</p>
@endif

<h2 id="sec-9">9. Rutas HTTP</h2>
<p>Generado con <code>php artisan route:list --json</code>. Excluye rutas <code>_dusk/*</code> y health <code>up</code>. Máximo <strong>320</strong> filas ordenadas por URI. Filas listadas: <strong>{{ $routeTotalListed }}</strong>.</p>
<p>Grupos principales en <code>routes/web.php</code>:</p>
<ul>
    <li><code>auth</code> — dashboard, storage fallback</li>
    <li><code>auth + password.must_change</code> — perfil de usuario</li>
    <li><code>auth + password.must_change + profile.complete</code> — panel admin completo</li>
</ul>
@if($routeTotalListed === 0)
    <p class="muted">No se obtuvieron rutas. Ejecute <code>php artisan route:list</code> en consola.</p>
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
@endif

@if(in_array($pdfSection, ['all', 'post_routes'], true))
@if($isPdf && $pdfSection === 'post_routes')
<p class="muted" style="margin-bottom:10px;"><strong>Manual técnico SAMS</strong> — Secciones 10–32.</p>
@endif

<h2 id="sec-10">10. Seguridad</h2>
<h3>10.1 Autenticación y contraseñas</h3>
<ul>
    <li>Contraseñas hasheadas con bcrypt (cast <code>hashed</code> en modelo User).</li>
    <li>Política de contraseñas fuertes en registro y reset (<code>Password::defaults()</code>).</li>
    <li>Flujo <code>must_change_password</code> obliga cambio tras alta administrativa.</li>
    <li>Throttle en registro: 6 intentos por minuto.</li>
</ul>
<h3>10.2 Cabeceras HTTP (<code>SecurityHeaders</code>)</h3>
<table>
    <thead><tr><th>Cabecera</th><th>Valor / efecto</th></tr></thead>
    <tbody>
        <tr><td><code>X-Frame-Options</code></td><td>SAMEORIGIN — anti-clickjacking</td></tr>
        <tr><td><code>X-Content-Type-Options</code></td><td>nosniff</td></tr>
        <tr><td><code>Referrer-Policy</code></td><td>strict-origin-when-cross-origin</td></tr>
        <tr><td><code>Content-Security-Policy</code></td><td>Restringe scripts/estilos a self + CDNs autorizados</td></tr>
        <tr><td><code>Strict-Transport-Security</code></td><td>Solo en HTTPS (max-age 1 año)</td></tr>
    </tbody>
</table>
<h3>10.3 Protección de datos y archivos</h3>
<ul>
    <li><strong>CSRF</strong> en todos los formularios web; token en meta y cabecera <code>X-CSRF-TOKEN</code> para AJAX.</li>
    <li><strong>Sanitización XSS</strong> de HTML en inspecciones (<code>HtmlSanitizer</code>).</li>
    <li><strong>Validación MIME</strong> en subidas (<code>UploadedFileStorage</code>).</li>
    <li><strong>Políticas:</strong> <code>UserPolicy</code>, <code>EquipoPolicy</code> para autorización granular.</li>
    <li><strong>Aislamiento tenant:</strong> <code>assertTenantOwns()</code> impide acceso cross-empresa.</li>
    <li><strong>Registro público</strong> deshabilitado por defecto (<code>SAMS_ALLOW_REGISTRATION=false</code>).</li>
</ul>

<h2 id="sec-11">11. Multi-empresa y autorización</h2>
<h3>11.1 EmpresaContext</h3>
<p>Servicio central que resuelve la empresa activa:</p>
<ol>
    <li>Si existe <code>empresa_activa_id</code> en sesión → esa empresa (admin global "entró" a una empresa).</li>
    <li>Si no, <code>auth()->user()->empresa_id</code> del usuario autenticado.</li>
    <li>Admin global sin sesión de empresa → contexto null (vista matriz).</li>
</ol>
<h3>11.2 Niveles de módulo (<code>config/sams.php</code>)</h3>
<table>
    <thead><tr><th>Nivel</th><th>Significado</th></tr></thead>
    <tbody>
        <tr><td><code>none</code></td><td>Módulo oculto / sin acceso</td></tr>
        <tr><td><code>view</code></td><td>Solo lectura</td></tr>
        <tr><td><code>edit</code></td><td>Lectura y escritura</td></tr>
    </tbody>
</table>
<p>Configuración por empresa en columna JSON <code>empresas.modulos</code>. Servicio <code>EmpresaModuleAuthorization</code> centraliza comprobaciones.</p>
<h3>11.3 Roles</h3>
<ul>
    <li><strong>administrador</strong> — rol global matriz (sin empresa_id en usuario).</li>
    <li><strong>{PREFIJO}-ADMIN</strong>, <strong>{PREFIJO}-USER</strong> — roles scoped por empresa.</li>
    <li>Permisos en JSON en tabla <code>roles.permissions</code> (claves: users, equipos, inspeccion, etc.).</li>
</ul>

<h2 id="sec-12">12. Módulos funcionales</h2>
<table>
    <thead><tr><th>Módulo</th><th>Clave config</th><th>Descripción</th></tr></thead>
    <tbody>
        <tr><td>Usuarios</td><td><code>users</code></td><td>Alta, edición, solicitudes pendientes, reset contraseña</td></tr>
        <tr><td>Roles</td><td><code>roles</code></td><td>Definición de roles y permisos JSON</td></tr>
        <tr><td>Cargos</td><td><code>cargos</code></td><td>Cargos organizacionales por empresa</td></tr>
        <tr><td>Grupos</td><td><code>grupos</code></td><td>Agrupación de usuarios</td></tr>
        <tr><td>Fabricantes</td><td><code>fabricantes</code></td><td>Catálogo de fabricantes de equipos</td></tr>
        <tr><td>Equipos / Inventario</td><td><code>equipos</code></td><td>CRUD equipos, imágenes, kits, ubicación</td></tr>
        <tr><td>Equipos en baja</td><td><code>equipos_baja</code></td><td>Registro y actas de baja</td></tr>
        <tr><td>Auditoría</td><td><code>auditoria</code></td><td>Traspasos y auditoría de inventario</td></tr>
        <tr><td>Material didáctico</td><td><code>material_didactico</code></td><td>Equipos de formación/capacitación</td></tr>
        <tr><td>Hoja de vida</td><td><code>hoja_vida</code></td><td>Plantillas y documentos HV por equipo</td></tr>
        <tr><td>Inspección</td><td><code>inspeccion</code></td><td>Plantillas, registros, PDF con firmas</td></tr>
        <tr><td>Exportar</td><td><code>exportar</code></td><td>Exportaciones masivas Excel/PDF</td></tr>
        <tr><td>Asignar</td><td><code>asignar</code></td><td>Asignación de equipos a usuarios con firmas</td></tr>
        <tr><td>Préstamos temporales</td><td><code>prestamos_temporales</code></td><td>Préstamo con fechas y devolución</td></tr>
        <tr><td>Empresa</td><td><code>empresa</code></td><td>Datos, branding, logos, módulos, códigos</td></tr>
        <tr><td>Sedes</td><td><code>sede</code></td><td>Ubicaciones físicas de la empresa</td></tr>
        <tr><td>Bodegas</td><td><code>bodega</code></td><td>Almacenes vinculados a sedes</td></tr>
        <tr><td>Sugerencias</td><td>—</td><td>Avisos de cumplimiento por empresa (<code>AvisoEmpresa</code>)</td></tr>
    </tbody>
</table>

<h2 id="sec-13">13. Modelo de datos</h2>
<h3>13.1 Entidades principales</h3>
<table>
    <thead><tr><th>Tabla / Modelo</th><th>Relación clave</th></tr></thead>
    <tbody>
        <tr><td><code>empresas</code> / Empresa</td><td>Raíz tenant; modulos JSON, branding, code_settings</td></tr>
        <tr><td><code>users</code> / User</td><td>role_id, cargo_id, grupo_id, empresa_id</td></tr>
        <tr><td><code>roles</code> / Role</td><td>permissions JSON</td></tr>
        <tr><td><code>sedes</code>, <code>bodegas</code>, <code>oficinas</code>, <code>espacios</code></td><td>Jerarquía ubicación → empresa_id</td></tr>
        <tr><td><code>tipo_equipos</code>, <code>clase_equipos</code></td><td>Taxonomía por empresa</td></tr>
        <tr><td><code>equipos</code> / Equipo</td><td>SoftDeletes; sede, bodega, fabricante, código único</td></tr>
        <tr><td><code>equipo_imagenes</code>, <code>equipo_kit_items</code></td><td>Anexos del equipo</td></tr>
        <tr><td><code>equipo_bajas</code></td><td>Registro de baja con acta PDF</td></tr>
        <tr><td><code>equipo_inspecciones</code></td><td>Inspecciones con HTML sanitizado</td></tr>
        <tr><td><code>inspeccion_plantillas</code></td><td>Plantillas por tipo/clase</td></tr>
        <tr><td><code>hoja_vida_plantillas</code>, <code>hoja_vida_documentos</code></td><td>HV por clase/equipo</td></tr>
        <tr><td><code>equipo_asignaciones</code></td><td>Asignaciones con firmas</td></tr>
        <tr><td><code>equipo_asignacion_solicitudes</code></td><td>Flujo solicitud → aceptación</td></tr>
        <tr><td><code>prestamos_temporales</code></td><td>Préstamos con items y fechas</td></tr>
        <tr><td><code>aviso_empresas</code></td><td>Sugerencias / cumplimiento</td></tr>
        <tr><td><code>codigos_reutilizables</code></td><td>Pool de códigos liberados</td></tr>
    </tbody>
</table>
<h3>13.2 Trait BelongsToEmpresa</h3>
<p>Aplica scope global <code>where empresa_id = contexto</code> en modelos tenant cuando hay empresa activa. Evita fugas de datos entre empresas en consultas Eloquent.</p>
<p class="muted">Total migraciones en proyecto: <strong>{{ $migrationCount ?? '—' }}</strong>.</p>

<h2 id="sec-14">14. Flujos de negocio principales</h2>
<h3>14.1 Alta de equipo (inventario)</h3>
<ol>
    <li>Usuario con permiso <code>equipos</code> accede a inventario.</li>
    <li>Selecciona tipo/clase; sistema genera código (<code>CodigoEquipoService</code>) con prefijo <code>IN-</code>.</li>
    <li>Completa datos, ubicación (sede/bodega/oficina/espacio), imágenes.</li>
    <li>Validación vía <code>StoreEquipoRequest</code>; archivos vía <code>UploadedFileStorage</code>.</li>
</ol>
<h3>14.2 Inspección periódica</h3>
<ol>
    <li>Administrador define plantilla por tipo/clase.</li>
    <li>Inspector completa formulario por equipo; HTML sanitizado al guardar.</li>
    <li>Generación PDF con firmas (Dompdf, imágenes locales/base64).</li>
    <li>Opción de dar de baja desde inspección si aplica.</li>
</ol>
<h3>14.3 Asignación de equipos</h3>
<ol>
    <li>Solicitud de asignación con items y destinatario.</li>
    <li>Aceptación con firma digital del receptor.</li>
    <li>Seguimiento y devolución con registro.</li>
</ol>
<h3>14.4 Préstamo temporal</h3>
<ol>
    <li>Creación con fecha fin; revisión administrativa.</li>
    <li>Entrega y devolución con verificación de items.</li>
</ol>
<h3>14.5 Baja de equipo</h3>
<ol>
    <li>Registro en módulo bajas; código pasa a prefijo <code>DB-</code>.</li>
    <li>Acta PDF generada; código puede ir a pool reutilizable.</li>
</ol>

<h2 id="sec-15">15. Middleware y sesión</h2>
<table>
    <thead><tr><th>Middleware</th><th>Función</th></tr></thead>
    <tbody>
        <tr><td><code>web</code> (grupo)</td><td>Sesión, cookies, CSRF</td></tr>
        <tr><td><code>PreventAuthPageCache</code></td><td>Evita caché en páginas de auth</td></tr>
        <tr><td><code>SecurityHeaders</code></td><td>CSP, HSTS, X-Frame-Options</td></tr>
        <tr><td><code>password.must_change</code></td><td>Redirige a contraseña segura si aplica</td></tr>
        <tr><td><code>profile.complete</code></td><td>Exige foto, firma, documento; excepciones: perfil, logout, manual técnico</td></tr>
        <tr><td><code>auth</code></td><td>Usuario autenticado requerido</td></tr>
    </tbody>
</table>
<p>Token CSRF expirado (419): redirección amigable a dashboard o login con mensaje de advertencia.</p>

<h2 id="sec-16">16. Prefijos de código de equipos</h2>
<table>
    <thead><tr><th>Prefijo</th><th>Contexto</th></tr></thead>
    <tbody>
        <tr><td><code>IN-</code></td><td>Inventario activo</td></tr>
        <tr><td><code>DB-</code></td><td>Equipo dado de baja</td></tr>
        <tr><td><code>AUD-</code></td><td>Auditoría / traspaso</td></tr>
        <tr><td><code>MD-</code></td><td>Material didáctico</td></tr>
    </tbody>
</table>
<p>Configuración avanzada por empresa en <code>code_settings</code> (JSON). Sincronización de códigos reutilizables según <code>SAMS_CODIGOS_SYNC_TTL</code>.</p>

<h2 id="sec-17">17. Archivos y límites PHP</h2>
<h3>17.1 Validación Laravel (aplicación)</h3>
<ul>
    <li>Logos empresa: <code>image|max:4096</code> (~4 MB).</li>
    <li>Imágenes equipo: <code>mimes:jpg,jpeg,png,webp|max:5120</code> (~5 MB).</li>
    <li>Firma perfil: límite base64 PNG en <code>ProfileController</code>.</li>
    <li>Excel/plantillas: validación MIME en <code>UploadedFileStorage::storePublicSpreadsheet</code>.</li>
</ul>
<h3>17.2 Límites PHP del servidor (al generar manual)</h3>
<table>
    <tbody>
        <tr><th>upload_max_filesize</th><td>{{ $phpLimits['upload_max_filesize'] }}</td></tr>
        <tr><th>post_max_size</th><td>{{ $phpLimits['post_max_size'] }}</td></tr>
        <tr><th>max_execution_time</th><td>{{ $phpLimits['max_execution_time'] }}</td></tr>
        <tr><th>memory_limit</th><td>{{ $phpLimits['memory_limit'] }}</td></tr>
    </tbody>
</table>
<p><code>UploadedFileStorage</code> evita fallos de <code>store()</code> en Windows cuando <code>getRealPath()</code> devuelve vacío.</p>

<h2 id="sec-18">18. Generación PDF (Dompdf)</h2>
<ul>
    <li><strong>Motor:</strong> <code>barryvdh/laravel-dompdf</code> con DejaVu Sans.</li>
    <li><strong>Manual técnico:</strong> fragmentos portrait + landscape (rutas) unidos con FPDI.</li>
    <li><strong>Inspecciones/HV:</strong> <code>isRemoteEnabled=true</code> para imágenes remotas cuando aplique.</li>
    <li><strong>pdftk:</strong> <code>mikehaertl/php-pdftk</code> — requiere binario en servidor si se invoca.</li>
    <li><strong>Recomendación:</strong> CSS simple (tablas), imágenes locales/base64, paginar documentos largos.</li>
</ul>

<h2 id="sec-19">19. Excel (Maatwebsite)</h2>
<p>Importación y exportación XLSX/CSV mediante PhpSpreadsheet. Para volúmenes grandes: colas (<code>ShouldQueue</code>) y lectura por chunks. Almacenamiento temporal en disco <code>public</code> con validación MIME.</p>

<h2 id="sec-21">21. Dependencias PHP — producción</h2>
<table class="data">
    <thead><tr><th>Paquete</th><th>Restricción</th><th>Instalada (lock)</th></tr></thead>
    <tbody>
    @forelse($composerRows as $row)
        <tr>
            <td><code>{{ $row['name'] }}</code></td>
            <td><code>{{ $row['constraint'] }}</code></td>
            <td>{{ $row['installed'] }}</td>
        </tr>
    @empty
        <tr><td colspan="3">Sin datos de Composer.</td></tr>
    @endforelse
    </tbody>
</table>

<h2 id="sec-22">22. Dependencias PHP — desarrollo</h2>
<table class="data">
    <thead><tr><th>Paquete</th><th>Restricción</th><th>Instalada (lock)</th></tr></thead>
    <tbody>
    @forelse($composerDevRows ?? [] as $row)
        <tr>
            <td><code>{{ $row['name'] }}</code></td>
            <td><code>{{ $row['constraint'] }}</code></td>
            <td>{{ $row['installed'] }}</td>
        </tr>
    @empty
        <tr><td colspan="3">Sin require-dev.</td></tr>
    @endforelse
    </tbody>
</table>

<h2 id="sec-23">23. Dependencias JavaScript (npm)</h2>
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
<p>Build: <code>vite.config.js</code> — entradas <code>resources/css/app.css</code>, <code>resources/js/app.js</code>, <code>resources/js/charts.js</code>.</p>

<h2 id="sec-24">24. CDN y recursos externos</h2>
<ul>
    <li><strong>Tailwind CDN:</strong> <code>cdn.tailwindcss.com</code> — layouts admin.</li>
    <li><strong>Lucide:</strong> <code>unpkg.com/lucide</code> — iconografía.</li>
    <li><strong>Chart.js:</strong> jsDelivr — gráficos dashboard.</li>
    <li><strong>Google Fonts:</strong> tipografías configurables por empresa.</li>
    <li><strong>Google Maps:</strong> geocodificación (requiere API key).</li>
</ul>

<h2 id="sec-25">25. Estructura de carpetas</h2>
<pre>app/Http/Controllers/   # Controladores por módulo
app/Http/Middleware/    # Middleware personalizado
app/Http/Requests/      # Validación de formularios
app/Models/             # Eloquent + BelongsToEmpresa
app/Policies/           # Autorización
app/Services/           # Lógica de dominio transversal
app/Support/            # Helpers (UploadedFileStorage, HtmlSanitizer)
bootstrap/app.php       # Registro middleware, excepciones
config/                 # Configuración
database/migrations/    # Esquema BD
database/seeders/       # Datos iniciales
resources/views/        # Plantillas Blade
routes/web.php          # Rutas HTTP
public/                 # Punto de entrada web
storage/app/public/     # Archivos subidos</pre>

<h2 id="sec-26">26. Mapa de controladores</h2>
<table>
    <thead><tr><th>Área funcional</th><th>Controlador(es)</th></tr></thead>
    <tbody>
        <tr><td>Panel / estadísticas</td><td><code>AdminController</code>, <code>DashboardController</code></td></tr>
        <tr><td>Usuarios y metadatos</td><td><code>UserController</code>, <code>RoleController</code>, <code>CargoController</code>, <code>GrupoController</code>, <code>FabricanteController</code></td></tr>
        <tr><td>Empresa / sedes / bodegas</td><td><code>EmpresaManagementController</code></td></tr>
        <tr><td>Inventario y anexos</td><td><code>EquipoInventarioController</code>, <code>EquipoManagementController</code>, <code>EquiposBajaController</code>, <code>EquiposAuditoriaController</code>, <code>MaterialDidacticoController</code>, <code>TraspasoController</code></td></tr>
        <tr><td>Formatos / HV / inspección / export</td><td><code>FormatosController</code>, <code>HojaVidaController</code>, <code>InspeccionController</code>, <code>ExportarController</code></td></tr>
        <tr><td>Asignar / préstamos</td><td><code>AsignarController</code>, <code>PrestamoTemporalController</code></td></tr>
        <tr><td>Sugerencias</td><td><code>SugerenciaController</code></td></tr>
        <tr><td>Documentación</td><td><code>ManualTecnicoController</code></td></tr>
        <tr><td>Auth / perfil</td><td><code>LoginController</code>, <code>RegisterController</code>, <code>ForcedPasswordController</code>, <code>ProfileController</code></td></tr>
    </tbody>
</table>

<h2 id="sec-27">27. Inventario de código fuente</h2>
<h3>Modelos (<code>app/Models</code>)</h3>
@if(!empty($modelFiles))
    <pre>@foreach($modelFiles as $m){{ $m }}
@endforeach</pre>
@endif
<h3>Controladores (<code>app/Http/Controllers</code>)</h3>
@if(!empty($controllerFiles))
    <pre>@foreach($controllerFiles as $c){{ $c }}
@endforeach</pre>
@endif
<h3>Middleware</h3>
@if(!empty($middlewareFiles))
    <pre>@foreach($middlewareFiles as $mw){{ $mw }}
@endforeach</pre>
@endif
<h3>Servicios</h3>
@if(!empty($serviceFiles))
    <pre>@foreach($serviceFiles as $s){{ $s }}
@endforeach</pre>
@endif
<h3>Comandos Artisan</h3>
@if(!empty($consoleCommands))
    <pre>@foreach($consoleCommands as $cmd){{ $cmd }}
@endforeach</pre>
@endif

<h2 id="sec-28">28. Pruebas (PHPUnit)</h2>
<ul>
    <li><code>php artisan test</code> — ejecuta <code>tests/Unit</code> y <code>tests/Feature</code>.</li>
    <li><code>tests/Feature/RouteSmokeTest.php</code> — smoke test de rutas críticas.</li>
    <li>Entorno testing configurado en <code>phpunit.xml</code>; preferir MySQL en Windows/Laragon.</li>
</ul>

<h2 id="sec-29">29. Despliegue en producción</h2>
<p>Guía corta: <code>docs/COMO-SUBIR.md</code>. En el PC se genera un ZIP con <code>php artisan sams:package</code> (incluye <code>vendor</code> y estilos). En el servidor se sube el ZIP <strong>sin tocar el .env</strong>, se importa el SQL si la base es nueva, y se abre <code>/inicio</code>. No hace falta Node en el hosting.</p>
<ol>
    <li>En el PC: <code>composer install --no-dev --optimize-autoloader</code> y <code>npm run build</code></li>
    <li><code>php artisan sams:package</code> y, si aplica, <code>php artisan sams:export-sql</code></li>
    <li>Subir <code>dist/sams-subir.zip</code> y descomprimir</li>
    <li>Importar SQL en phpMyAdmin o usar el modo “crear tablas” del instalador</li>
    <li>Abrir <code>/inicio</code> y recargar con Ctrl+F5</li>
    <li>Permisos de escritura en <code>storage/</code> y <code>bootstrap/cache/</code></li>
    <li>HTTPS; HSTS se activa automáticamente</li>
</ol>

<h2 id="sec-30">30. Operaciones y mantenimiento</h2>
<table>
    <thead><tr><th>Tarea</th><th>Procedimiento</th></tr></thead>
    <tbody>
        <tr><td>Backup BD</td><td> mysqldump programado; incluir antes de migraciones</td></tr>
        <tr><td>Backup archivos</td><td>Copiar <code>storage/app/public</code></td></tr>
        <tr><td>Logs</td><td><code>storage/logs/laravel.log</code></td></tr>
        <tr><td>Limpiar caché</td><td><code>php artisan cache:clear config:clear view:clear</code></td></tr>
        <tr><td>Actualizar dependencias</td><td><code>composer update</code> en staging primero; revisar changelog Laravel</td></tr>
        <tr><td>Seeders demo</td><td><code>UserDemoSeeder</code> — solo desarrollo</td></tr>
    </tbody>
</table>

<h2 id="sec-31">31. Resolución de problemas</h2>
<table>
    <thead><tr><th>Síntoma</th><th>Causa probable</th><th>Solución</th></tr></thead>
    <tbody>
        <tr><td>419 Page Expired</td><td>Token CSRF expirado</td><td>Recargar; verificar SESSION_DRIVER</td></tr>
        <tr><td>403 en módulo</td><td>Permiso o módulo empresa = none</td><td>Revisar roles y <code>empresas.modulos</code></td></tr>
        <tr><td>Imágenes no cargan</td><td>Sesión o tenant; no usar storage:link</td><td>Iniciar sesión; verificar que <code>public/.htaccess</code> reescribe <code>/storage</code> a Laravel</td></tr>
        <tr><td>500 en subida Excel</td><td>MIME o límite PHP</td><td>Revisar upload_max_filesize; logs</td></tr>
        <tr><td>PDF en blanco</td><td>CSS complejo o memoria</td><td>Simplificar plantilla; subir memory_limit</td></tr>
        <tr><td>Estilos desactualizados o logo enorme</td><td>Falta <code>public/build</code> o quedó el archivo <code>public/hot</code></td><td>Subir el ZIP de <code>sams:package</code>; borrar <code>public/hot</code>; recargar con Ctrl+F5</td></tr>
        <tr><td>Cross-tenant data</td><td>EmpresaContext incorrecto</td><td>Verificar sesión empresa_activa_id</td></tr>
    </tbody>
</table>

<h2 id="sec-32">32. Descarga del manual</h2>
<ul>
    <li><strong>Condición:</strong> usuario autenticado con <code>empresa_id === null</code> (administrador global).</li>
    <li><strong>HTML:</strong> <code>GET /admin/docs/manual-tecnico</code> — ruta <code>admin.docs.manual-tecnico</code>.</li>
    <li><strong>PDF:</strong> <code>GET /admin/docs/manual-tecnico.pdf</code> — ruta <code>admin.docs.manual-tecnico-pdf</code>.</li>
</ul>

<footer class="muted" style="margin-top:2.5rem;border-top:2px solid #cbd5e1;padding-top:1rem;">
    <p><strong>{{ $docId }}</strong> · Versión {{ $docVersion }} · {{ $organization }}</p>
    <p>Documento generado automáticamente por SAMS el {{ $generatedHuman }}. Versionar junto al código fuente del repositorio.</p>
    <p>Para listado completo de rutas sin límite: <code>php artisan route:list</code> en el entorno correspondiente.</p>
</footer>
@endif
