@extends('layouts.admin-layout')

@section('title', 'Gestión de Cargos - SAMS')
@section('header-title', 'Gestión de Cargos')
@section('header-subtitle', 'Administración completa de cargos del sistema')

@section('header-actions')
<div class="flex flex-wrap items-center gap-2">
    <button onclick="showCreateCargoModal()" class="pw-btn-success px-4 py-2 rounded-lg">
        <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>
        Nuevo Cargo
    </button>
</div>
@endsection

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                    <i data-lucide="briefcase" class="w-6 h-6 text-white"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Total Cargos</h3>
                    <p class="text-2xl font-bold text-blue-600">{{ $stats['total'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        
        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                    <i data-lucide="user-check" class="w-6 h-6 text-white"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Usuarios Asignados</h3>
                    <p class="text-2xl font-bold text-green-600">{{ $stats['users_assigned'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        
        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
                    <i data-lucide="bar-chart" class="w-6 h-6 text-white"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Promedio por Cargo</h3>
                    <p class="text-2xl font-bold text-purple-600">{{ $stats['avg_users_per_cargo'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters + Pagination -->
    <div class="pw-card bg-white rounded-xl shadow-lg overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Lista de Cargos</h3>
                <form method="GET" data-auto-submit="1" data-auto-submit-debounce="700" class="flex flex-wrap items-end gap-2">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Buscar</label>
                        <input name="q" value="{{ $q ?? '' }}" type="text" placeholder="Nombre o descripción..." class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Estado</label>
                        <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>Todos</option>
                            <option value="active" {{ ($status ?? 'all') === 'active' ? 'selected' : '' }}>Activos</option>
                            <option value="inactive" {{ ($status ?? 'all') === 'inactive' ? 'selected' : '' }}>Inactivos</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Filas</label>
                        <select name="per_page" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach(['10','15','30','50','100','all'] as $n)
                                <option value="{{ $n }}" {{ (string)($perPage ?? '15') === (string)$n ? 'selected' : '' }}>{{ $n === 'all' ? 'Todas' : $n }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('cargos.complete') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">Limpiar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="px-6 py-3 border-b border-gray-200 flex items-center justify-between">
            <div class="text-sm text-gray-600">
                Mostrando {{ $cargos->firstItem() ?? 0 }} - {{ $cargos->lastItem() ?? 0 }} de {{ $cargos->total() ?? 0 }}
            </div>
            <div>
                {{ $cargos->onEachSide(1)->links() }}
            </div>
        </div>
    </div>

    <!-- Cargos Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($cargos as $cargo)
        <div class="pw-card bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-blue-500 rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-lg">{{ substr($cargo->name, 0, 2) }}</span>
                </div>
                <div class="flex space-x-2">
                    <button onclick="editCargo({{ $cargo->id }})" class="pw-btn-icon-edit inline-flex items-center justify-center rounded-lg" title="Editar">
                        <i data-lucide="edit"></i>
                    </button>
                    <button onclick="deleteCargo({{ $cargo->id }})" class="pw-btn-icon-delete inline-flex items-center justify-center rounded-lg" title="Eliminar">
                        <i data-lucide="trash-2"></i>
                    </button>
                    <button onclick="toggleCargoStatus({{ $cargo->id }})" class="inline-flex items-center justify-center rounded-lg {{ $cargo->activo ? 'pw-btn-icon-toggle-active' : 'pw-btn-icon-toggle-inactive' }}" title="{{ $cargo->activo ? 'Desactivar' : 'Activar' }}">
                        <i data-lucide="{{ $cargo->activo ? 'toggle-right' : 'toggle-left' }}"></i>
                    </button>
                </div>
            </div>
            
            <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ $cargo->name }}</h3>
            <p class="text-gray-600 text-sm mb-4">{{ $cargo->description ?? 'Sin descripción' }}</p>
            
            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center text-gray-500">
                    <i data-lucide="users" class="w-4 h-4 mr-1"></i>
                    <span>{{ $cargo->users_count }} usuarios</span>
                </div>
                <div class="px-2 py-1 rounded-full text-xs {{ $cargo->activo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                    {{ $cargo->activo ? 'Activo' : 'Inactivo' }}
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $cargos->onEachSide(1)->links() }}
    </div>

    @if($cargos->isEmpty())
    <div class="text-center py-12">
        <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
            <i data-lucide="briefcase" class="w-8 h-8 text-gray-400"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No hay cargos registrados</h3>
        <p class="text-gray-500 mb-4">Comienza creando tu primer cargo para organizar a los usuarios</p>
        <button onclick="showCreateCargoModal()" class="pw-btn-primary px-4 py-2 rounded-lg">
            <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>
            Crear Primer Cargo
        </button>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8 mb-4">
        <div class="pw-card bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-1">Top cargos por usuarios asignados</h3>
            <p class="text-xs text-gray-500 mb-4">Hasta 10 cargos con más usuarios (respeta filtros actuales).</p>
            <div class="h-64 relative">
                <canvas id="chartCargosUsersTop"></canvas>
            </div>
        </div>
        <div class="pw-card bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-1">Estado de cargos</h3>
            <p class="text-xs text-gray-500 mb-4">Activos vs inactivos en el alcance del listado.</p>
            <div class="h-64 relative max-w-sm mx-auto">
                <canvas id="chartCargosActivoPie"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Create Cargo Modal -->
<div id="createCargoModal" class="fixed inset-0 hidden z-[11000] flex items-start md:items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="createCargoTitle">
    <div class="pw-modal-content pw-modal-md">
        <div class="pw-modal-header">
            <div class="pw-modal-header-main">
                <div class="pw-modal-header-icon" aria-hidden="true">
                    <i data-lucide="briefcase" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 id="createCargoTitle" class="pw-modal-title">Crear cargo</h3>
                    <p class="pw-modal-subtitle">Registra un cargo para asignarlo a los usuarios.</p>
                </div>
            </div>
            <button type="button" class="pw-modal-close" onclick="hideCreateCargoModal()" aria-label="Cerrar">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form action="{{ route('cargos.store') }}" method="POST">
            @csrf
            <div class="pw-modal-body">
                <div class="space-y-4">
                    <div>
                        <label>Nombre del cargo <span class="pw-req">*</span></label>
                        <input type="text" name="name" required class="w-full">
                    </div>
                    <div>
                        <label>Descripción</label>
                        <textarea name="description" rows="3" class="w-full"></textarea>
                    </div>
                </div>
            </div>
            <div class="pw-modal-footer">
                <button type="button" onclick="hideCreateCargoModal()" class="pw-btn-secondary px-4 py-2.5 rounded-lg text-sm">
                    Cancelar
                </button>
                <button type="submit" class="pw-btn-primary px-4 py-2.5 rounded-lg text-sm">
                    Crear cargo
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Cargo Modal -->
<div id="editCargoModal" class="fixed inset-0 hidden z-[11000] flex items-start md:items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="editCargoTitle">
    <div class="pw-modal-content pw-modal-md">
        <div class="pw-modal-header">
            <div class="pw-modal-header-main">
                <div class="pw-modal-header-icon" aria-hidden="true">
                    <i data-lucide="briefcase" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 id="editCargoTitle" class="pw-modal-title">Editar cargo</h3>
                    <p class="pw-modal-subtitle">Actualiza el nombre o la descripción del cargo.</p>
                </div>
            </div>
            <button type="button" class="pw-modal-close" onclick="hideEditCargoModal()" aria-label="Cerrar">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form id="editCargoForm" method="POST">
            @csrf
            @method('PUT')
            <div class="pw-modal-body">
                <div class="space-y-4">
                    <div>
                        <label>Nombre del cargo <span class="pw-req">*</span></label>
                        <input type="text" id="editNombre" name="name" required class="w-full">
                    </div>
                    <div>
                        <label>Descripción</label>
                        <textarea id="editDescripcion" name="description" rows="3" class="w-full"></textarea>
                    </div>
                </div>
            </div>
            <div class="pw-modal-footer">
                <button type="button" onclick="hideEditCargoModal()" class="pw-btn-secondary px-4 py-2.5 rounded-lg text-sm">
                    Cancelar
                </button>
                <button type="submit" class="pw-btn-primary px-4 py-2.5 rounded-lg text-sm">
                    Actualizar cargo
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
@vite('resources/js/charts.js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const waitChart = function(cb) {
        if (typeof Chart !== 'undefined') { cb(); return; }
        let n = 0;
        const t = setInterval(function() {
            n++;
            if (typeof Chart !== 'undefined') { clearInterval(t); cb(); }
            else if (n > 100) { clearInterval(t); }
        }, 40);
    };
    waitChart(function() {
        const topSeries = @json($chartTopUsersByCargo ?? []);
        const elTop = document.getElementById('chartCargosUsersTop');
        if (elTop && topSeries.length) {
            new Chart(elTop, {
                type: 'bar',
                data: {
                    labels: topSeries.map(function(x) { return x.label; }),
                    datasets: [{
                        label: 'Usuarios',
                        data: topSeries.map(function(x) { return x.count; }),
                        backgroundColor: topSeries.map(function(_, i) {
                            const a = 0.35 + (i / Math.max(topSeries.length, 1)) * 0.45;
                            return 'rgba(16, 185, 129, ' + a + ')';
                        }),
                        borderColor: '#059669',
                        borderWidth: 1,
                        borderRadius: 6,
                        maxBarThickness: 22
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 900, easing: 'easeOutQuart' },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15,23,42,0.92)',
                            titleColor: '#f8fafc',
                            bodyColor: '#e2e8f0',
                            padding: 10,
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(148,163,184,0.25)' } },
                        y: { ticks: { autoSkip: false, font: { size: 10 } }, grid: { display: false } }
                    }
                }
            });
        }
        const elPie = document.getElementById('chartCargosActivoPie');
        const act = {{ (int) ($stats['cargos_active'] ?? 0) }};
        const inact = {{ (int) ($stats['cargos_inactive'] ?? 0) }};
        if (elPie && (act + inact) > 0) {
            new Chart(elPie, {
                type: 'doughnut',
                data: {
                    labels: ['Activos', 'Inactivos'],
                    datasets: [{
                        data: [act, inact],
                        backgroundColor: ['#22c55e', '#94a3b8'],
                        borderColor: '#ffffff',
                        borderWidth: 3,
                        hoverOffset: 14
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '58%',
                    animation: { animateRotate: true, duration: 1000 },
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, padding: 16 } },
                        tooltip: {
                            backgroundColor: 'rgba(15,23,42,0.92)',
                            callbacks: {
                                label: function(ctx) {
                                    const t = ctx.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                                    const v = ctx.raw;
                                    const pct = t ? Math.round((v / t) * 100) : 0;
                                    return ' ' + ctx.label + ': ' + v + ' (' + pct + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    });

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});

function resourceApiUrl(marker, id, suffix) {
    const path = window.location.pathname || '';
    const idx = path.indexOf(marker);
    const base = idx >= 0 ? path.slice(0, idx + marker.length) : marker;
    return base + '/' + encodeURIComponent(String(id)) + (suffix || '');
}
function cargosApiUrl(id, suffix) { return resourceApiUrl('/cargos', id, suffix); }
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}
async function parseJsonResponse(response) {
    const ct = response.headers.get('content-type') || '';
    if (ct.includes('application/json')) return await response.json().catch(() => null);
    return null;
}

function showCreateCargoModal() {
    document.getElementById('createCargoModal').classList.remove('hidden');
}

function hideCreateCargoModal() {
    document.getElementById('createCargoModal').classList.add('hidden');
}

function showEditCargoModal() {
    document.getElementById('editCargoModal').classList.remove('hidden');
}

function hideEditCargoModal() {
    document.getElementById('editCargoModal').classList.add('hidden');
}

function editCargo(id) {
    fetch(cargosApiUrl(id, '/edit'), {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(async (response) => {
            const data = await parseJsonResponse(response);
            if (!response.ok || !data) throw new Error(data?.message || 'No se pudo cargar el cargo.');
            return data;
        })
        .then(data => {
            document.getElementById('editNombre').value = data.name;
            document.getElementById('editDescripcion').value = data.description || '';
            document.getElementById('editCargoForm').action = cargosApiUrl(id);
            showEditCargoModal();
        })
        .catch(error => alert(error?.message || 'Error al cargar el cargo.'));
}

function deleteCargo(id) {
    if (!confirm('¿Estás seguro de eliminar este cargo?')) return;
    fetch(cargosApiUrl(id), {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(async (response) => {
        const data = await parseJsonResponse(response);
        if (!response.ok || !data?.success) throw new Error(data?.message || 'No se pudo eliminar el cargo.');
        return data;
    })
    .then(() => location.reload())
    .catch(error => alert(error?.message || 'Error al eliminar el cargo.'));
}

function toggleCargoStatus(id) {
    fetch(cargosApiUrl(id, '/toggle-status'), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(async (response) => {
        const data = await parseJsonResponse(response);
        if (!response.ok || !data?.success) throw new Error(data?.message || 'Error al cambiar el estado');
        return data;
    })
    .then(() => location.reload())
    .catch(error => alert(error?.message || 'Error al cambiar el estado.'));
}
</script>
@endsection
