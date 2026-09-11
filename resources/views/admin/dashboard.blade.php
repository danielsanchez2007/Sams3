@extends('layouts.admin-layout')

@section('title', 'Dashboard - SAMS')
@section('header-title', 'Dashboard Principal')
@section('header-subtitle', 'Panel de control y estadísticas del sistema')


@section('content')
<div class="dash-wrapper">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- ── Welcome Banner ── --}}
    <div class="welcome-banner">
        <div>
            <h1>¡Bienvenido, {{ Auth::user()->name }}!</h1>
            <p>Panel de administración · Sistema de Gestión de Mantenimiento y Seguimiento</p>
        </div>
        <div class="welcome-date">
            <div class="label">Fecha actual</div>
            <div class="value">{{ now()->format('d/m/Y') }}</div>
        </div>
    </div>

    {{-- ── Statistics Cards ── --}}
    @php
        $cards = [
            ['icon'=>'users',   'title'=>'Usuarios', 'count'=>$stats['users'] ?? 0,        'desc'=>'Total registrados'],
            ['icon'=>'monitor', 'title'=>'Equipos',  'count'=>$stats['equipos'] ?? 0,       'desc'=>'Total registrados'],
            ['icon'=>'layers',  'title'=>'Tipos',    'count'=>$stats['tiposEquipos'] ?? 0,  'desc'=>'Tipos de equipos'],
            ['icon'=>'shield',  'title'=>'Clases',   'count'=>$stats['clasesEquipos'] ?? 0, 'desc'=>'Clases de equipos'],
        ];
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
        @foreach ($cards as $card)
        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-icon">
                    <i data-lucide="{{ $card['icon'] }}" class="w-5 h-5"></i>
                </div>
                <span class="stat-label">{{ $card['title'] }}</span>
            </div>
            <div>
                <div class="stat-count">{{ $card['count'] }}</div>
                <div class="stat-desc">{{ $card['desc'] }}</div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ── Small Charts ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
        <div class="section-card">
            <p class="section-title">Usuarios vs Equipos</p>
            <p class="section-subtitle">Comparativa general del sistema</p>
            <div class="dashboard-chart-box">
                <canvas id="chartUsersEquipos" role="img" aria-label="Usuarios vs Equipos"></canvas>
            </div>
        </div>
        <div class="section-card">
            <p class="section-title">Equipos por origen</p>
            <p class="section-subtitle">Distribución por tipo de código</p>
            <div class="dashboard-chart-box dashboard-chart-box--centered">
                <canvas id="chartOrigenResumen" role="img" aria-label="Equipos por origen"></canvas>
            </div>
        </div>
        <div class="section-card">
            <p class="section-title">Top 5 empresas</p>
            <p class="section-subtitle">Empresas con más equipos registrados</p>
            <div class="dashboard-chart-box">
                <canvas id="chartEmpresasMini" role="img" aria-label="Equipos por empresa"></canvas>
            </div>
        </div>
    </div>

    {{-- ── Quick Actions ── --}}
    @if(!auth()->user()->empresa_id)
    <div class="section-card mb-6">
        <p class="section-title">Acciones Rápidas</p>
        <div class="section-divider"></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <button onclick="location.href='{{ route('users.create') }}'" class="action-btn" aria-label="Crear nuevo usuario">
                <i data-lucide="user-plus" class="w-4 h-4"></i> Nuevo Usuario
            </button>
            <button onclick="location.href='{{ route('roles.create') }}'" class="action-btn" aria-label="Crear nuevo rol">
                <i data-lucide="shield-plus" class="w-4 h-4"></i> Nuevo Rol
            </button>
            <button onclick="location.href='{{ route('cargos.create') }}'" class="action-btn" aria-label="Crear nuevo cargo">
                <i data-lucide="briefcase" class="w-4 h-4"></i> Nuevo Cargo
            </button>
            <button onclick="location.href='{{ route('grupos.create') }}'" class="action-btn" aria-label="Crear nuevo grupo">
                <i data-lucide="users" class="w-4 h-4"></i> Nuevo Grupo
            </button>
            <button onclick="location.href='{{ route('fabricantes.create') }}'" class="action-btn" aria-label="Crear nuevo fabricante">
                <i data-lucide="factory" class="w-4 h-4"></i> Nuevo Fabricante
            </button>
            <button onclick="location.href='{{ route('equipos.gestion') }}'" class="action-btn" aria-label="Gestión de equipos">
                <i data-lucide="server" class="w-4 h-4"></i> Gestión Equipos
            </button>
        </div>
    </div>
    @endif

    {{-- ── Big Distribution Chart ── --}}
    <div class="section-card">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-4">
            <div>
                <p class="section-title">Distribución de equipos</p>
                <p class="section-subtitle section-subtitle--compact">Filtra por empresa, sede y bodega para ver la distribución por origen.</p>
            </div>
            <div class="flex flex-col sm:flex-row flex-wrap gap-3 items-start sm:items-end">
                <div class="flex items-center gap-2">
                    <span class="dashboard-view-label">Vista</span>
                    <div class="chart-toggle" role="group" aria-label="Tipo de gráfico">
                        <button type="button" data-distrib-type="bar"       class="distrib-type-btn active">Barras</button>
                        <button type="button" data-distrib-type="line"      class="distrib-type-btn">Línea</button>
                        <button type="button" data-distrib-type="polarArea" class="distrib-type-btn">Polar</button>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <div class="filter-group">
                        <label for="filtroEmpresa">Empresa</label>
                        <select id="filtroEmpresa" class="filter-select" aria-controls="chartDistribucionEquipos">
                            <option value="">Todas</option>
                            @foreach($empresas as $empresa)
                                <option value="{{ $empresa->id }}" {{ (isset($empresaActivaId) && $empresaActivaId == $empresa->id) ? 'selected' : '' }}>{{ $empresa->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filtroSede">Sede</label>
                        <select id="filtroSede" class="filter-select" aria-controls="chartDistribucionEquipos">
                            <option value="">Todas</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filtroBodega">Bodega</label>
                        <select id="filtroBodega" class="filter-select" aria-controls="chartDistribucionEquipos">
                            <option value="">Todas</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="section-divider"></div>
        <div class="dashboard-chart-distribucion">
            <canvas id="chartDistribucionEquipos" role="img" aria-label="Distribución de equipos"></canvas>
        </div>
    </div>

</div>
</div>
@endsection

@section('scripts')
<x-sams-assets :entries="['resources/js/charts.js']" />
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    const chartDataUrl  = @json(route('admin.dashboard.equipos-chart-data'));
    const bodegas       = @json($bodegas ?? []);
    const sedes         = @json($sedes ?? []);
    const totalUsers    = {{ $stats['users'] ?? 0 }};
    const totalEquipos  = {{ $stats['equipos'] ?? 0 }};

    // ── Palette: only blues & slate ──
    const PALETTE = ['#173461','#2563eb','#60a5fa','#93c5fd','#bfdbfe'];

    const tooltipTheme = {
        backgroundColor: '#173461',
        titleColor: '#ffffff',
        bodyColor: '#dbeafe',
        padding: 12,
        cornerRadius: 8,
        borderColor: '#2563eb',
        borderWidth: 1
    };

    const ctxOrigen      = document.getElementById('chartOrigenResumen');
    const ctxEmpMini     = document.getElementById('chartEmpresasMini');
    const ctxDistrib     = document.getElementById('chartDistribucionEquipos');
    const ctxUsersEq     = document.getElementById('chartUsersEquipos');
    const filtroEmpresa  = document.getElementById('filtroEmpresa');
    const filtroSede     = document.getElementById('filtroSede');
    const filtroBodega   = document.getElementById('filtroBodega');

    let chartDistrib = null, chartOrigen = null, chartEmpMini = null, chartUsersEq = null;
    let distribType = 'bar', lastCounts = {};

    // ── Helpers ──
    function actualizarSedes() {
        const id = filtroEmpresa.value;
        filtroSede.innerHTML = '<option value="">Todas</option>';
        sedes.filter(s => !id || String(s.empresa_id) === id)
             .forEach(s => filtroSede.appendChild(new Option(s.nombre, s.id)));
    }
    function actualizarBodegas() {
        const id = filtroEmpresa.value;
        filtroBodega.innerHTML = '<option value="">Todas</option>';
        bodegas.filter(b => !id || String(b.empresa_id) === id)
               .forEach(b => filtroBodega.appendChild(new Option(b.nombre, b.id)));
    }
    function buildQuery() {
        const p = new URLSearchParams();
        if (filtroEmpresa.value) p.set('empresa_id', filtroEmpresa.value);
        if (filtroSede.value)    p.set('sede_id',    filtroSede.value);
        if (filtroBodega.value)  p.set('bodega_id',  filtroBodega.value);
        return p.toString() ? chartDataUrl + '?' + p : chartDataUrl;
    }
    function fetchData(url) {
        return fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        }).then(r => { if (!r.ok) throw new Error(); return r.json(); });
    }

    // ── Toggle buttons ──
    function setToggle(active) {
        document.querySelectorAll('.distrib-type-btn').forEach(btn => {
            const on = btn.dataset.distribType === active;
            btn.classList.toggle('active', on);
        });
    }

    // ── Distribution chart ──
    function buildDistrib(counts) {
        lastCounts = counts || {};
        const labels = Object.keys(lastCounts);
        const data   = Object.values(lastCounts);
        const type   = distribType;
        if (chartDistrib) { chartDistrib.destroy(); chartDistrib = null; }

        const ds = {
            label: 'Equipos', data,
            borderWidth: type === 'line' ? 2 : (type === 'polarArea' ? 1 : 0),
            borderSkipped: false,
            borderRadius: type === 'bar' ? 8 : 0
        };
        if (type === 'line') {
            Object.assign(ds, {
                backgroundColor: 'rgba(37,99,235,0.10)',
                borderColor: '#2563eb', fill: true, tension: 0.35,
                pointRadius: 5, pointHoverRadius: 8,
                pointBackgroundColor: '#ffffff', pointBorderColor: '#2563eb', pointBorderWidth: 2
            });
        } else if (type === 'polarArea') {
            ds.backgroundColor = labels.map((_, i) => PALETTE[i % PALETTE.length] + 'cc');
            ds.borderColor = '#ffffff';
        } else {
            ds.backgroundColor = labels.map((_, i) => PALETTE[i % PALETTE.length]);
        }

        const opts = {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            animation: { duration: 700, easing: 'easeOutQuart' },
            plugins: {
                legend: { display: type === 'polarArea', position: 'right', labels: { color: '#475569', usePointStyle: true, font: { size: 11 } } },
                tooltip: Object.assign({ callbacks: { label: ctx => {
                    const sum = ctx.dataset.data.reduce((a,b)=>a+b,0);
                    const pct = sum ? Math.round((ctx.raw/sum)*100) : 0;
                    return ` ${ctx.label||''}: ${ctx.raw} (${pct}%)`;
                }}}, tooltipTheme)
            }
        };
        if (type === 'polarArea') {
            opts.scales = { r: { beginAtZero: true, ticks: { color: '#94a3b8', backdropColor: 'transparent' }, grid: { color: '#e2e8f0' }, pointLabels: { color: '#475569', font: { size: 11 } } } };
        } else {
            opts.scales = {
                x: { grid: { display: false }, ticks: { color: '#94a3b8', maxRotation: type==='bar'?30:0, font: { weight: '500', size: 11 } } },
                y: { beginAtZero: true, ticks: { precision: 0, color: '#94a3b8', font: { size: 11 } }, grid: { color: '#f1f5f9' } }
            };
        }
        chartDistrib = new Chart(ctxDistrib, { type, data: { labels, datasets: [ds] }, options: opts });
    }

    // ── Origen donut ──
    function buildOrigen(origenCounts) {
        if (!ctxOrigen) return;
        const labels = Object.keys(origenCounts || {});
        const data   = Object.values(origenCounts || {});
        const colors = labels.map((_, i) => PALETTE[i % PALETTE.length]);
        if (chartOrigen) {
            chartOrigen.data.labels = labels;
            chartOrigen.data.datasets[0].data = data;
            chartOrigen.data.datasets[0].backgroundColor = colors;
            chartOrigen.update('active'); return;
        }
        chartOrigen = new Chart(ctxOrigen, {
            type: 'doughnut',
            data: { labels, datasets: [{ data, backgroundColor: colors, borderColor: '#ffffff', borderWidth: 3, hoverOffset: 12 }] },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '60%',
                animation: { animateRotate: true, duration: 900, easing: 'easeOutCubic' },
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true, padding: 10, color: '#475569', font: { size: 10 } } },
                    tooltip: Object.assign({ callbacks: { label: ctx => {
                        const total = ctx.dataset.data.reduce((a,b)=>a+b,0);
                        const pct = total ? Math.round((ctx.raw/total)*100) : 0;
                        return ` ${ctx.label}: ${ctx.raw} (${pct}%)`;
                    }}}, tooltipTheme)
                }
            }
        });
    }

    // ── Empresa bar ──
    function buildEmpresa(empresaTop) {
        if (!ctxEmpMini || !empresaTop) return;
        const labels = empresaTop.map(x => x.nombre);
        const counts = empresaTop.map(x => x.count);
        const colors = labels.map((_, i) => PALETTE[i % PALETTE.length]);
        if (chartEmpMini) {
            chartEmpMini.data.labels = labels;
            chartEmpMini.data.datasets[0].data = counts;
            chartEmpMini.data.datasets[0].backgroundColor = colors;
            chartEmpMini.update('active'); return;
        }
        chartEmpMini = new Chart(ctxEmpMini, {
            type: 'bar',
            data: { labels, datasets: [{ label: 'Equipos', data: counts, backgroundColor: colors, borderColor: 'transparent', borderWidth: 0, borderRadius: 6, maxBarThickness: 22 }] },
            options: {
                indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                animation: { duration: 900, easing: 'easeOutQuart' },
                plugins: { legend: { display: false }, tooltip: tooltipTheme },
                scales: {
                    x: { beginAtZero: true, ticks: { precision: 0, color: '#94a3b8', font: { size: 10 } }, grid: { color: '#f1f5f9' } },
                    y: { grid: { display: false }, ticks: { color: '#475569', font: { size: 10 } } }
                }
            }
        });
    }

    // ── Users vs Equipos ──
    function buildUsersEq() {
        if (!ctxUsersEq) return;
        if (chartUsersEq) chartUsersEq.destroy();
        chartUsersEq = new Chart(ctxUsersEq, {
            type: 'bar',
            data: {
                labels: ['Usuarios', 'Equipos'],
                datasets: [{
                    data: [totalUsers, totalEquipos],
                    backgroundColor: ['#2563eb', '#173461'],
                    borderColor: 'transparent', borderWidth: 0,
                    borderRadius: 10, borderSkipped: false
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                animation: { duration: 1000, easing: 'easeOutQuart' },
                plugins: {
                    legend: { display: false },
                    tooltip: Object.assign({ callbacks: { label: ctx => ` ${ctx.label}: ${ctx.raw}` } }, tooltipTheme)
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#475569', font: { weight: '600', size: 12 } } },
                    y: { beginAtZero: true, suggestedMax: Math.max(totalUsers, totalEquipos, 1) * 1.2, ticks: { precision: 0, color: '#94a3b8' }, grid: { color: '#f1f5f9' } }
                }
            }
        });
    }

    // ── Wait for Chart.js ──
    function waitChart(cb) {
        if (typeof Chart !== 'undefined') { cb(); return; }
        let n = 0;
        const t = setInterval(() => { n++; if (typeof Chart !== 'undefined') { clearInterval(t); cb(); } else if (n > 100) clearInterval(t); }, 40);
    }

    waitChart(() => {
        buildUsersEq();

        document.querySelectorAll('.distrib-type-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                distribType = btn.dataset.distribType || 'bar';
                setToggle(distribType);
                buildDistrib(lastCounts);
            });
        });
        setToggle('bar');

        fetchData(chartDataUrl).then(p => {
            buildOrigen(p.origen_counts || {});
            buildEmpresa(p.empresa_top || []);
            buildDistrib(p.distribucion || {});

            actualizarSedes(); actualizarBodegas();

            const onChange = () => fetchData(buildQuery()).then(p => buildDistrib(p.distribucion || {})).catch(()=>{});
            filtroEmpresa.addEventListener('change', () => { actualizarSedes(); actualizarBodegas(); onChange(); });
            filtroSede.addEventListener('change',    () => { actualizarBodegas(); onChange(); });
            filtroBodega.addEventListener('change',  onChange);
        }).catch(() => {});
    });
});
</script>
@endsection