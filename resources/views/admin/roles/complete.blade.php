@extends('layouts.admin-layout')

@section('title', 'Gestión de Roles - SAMS')
@section('header-title', 'Gestión de Roles')
@section('header-subtitle', 'Administración completa de roles del sistema')

@section('header-actions')
<div class="flex flex-wrap items-center gap-2">
    <button onclick="showCreateRoleModal()" class="pw-btn-success px-4 py-2 rounded-lg">
        <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>
        Nuevo Rol
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
                    <i data-lucide="shield" class="w-6 h-6 text-white"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Total Roles</h3>
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
                    <h3 class="text-lg font-semibold text-gray-800">Promedio por Rol</h3>
                    <p class="text-2xl font-bold text-purple-600">{{ $stats['avg_users_per_role'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters + Pagination -->
    <div class="pw-card bg-white rounded-xl shadow-lg overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Lista de Roles</h3>
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
                        <a href="{{ route('roles.complete') }}" class="pw-btn-secondary px-4 py-2 rounded-lg">Limpiar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="px-6 py-3 border-b border-gray-200 flex items-center justify-between">
            <div class="text-sm text-gray-600">
                Mostrando {{ $roles->firstItem() ?? 0 }} - {{ $roles->lastItem() ?? 0 }} de {{ $roles->total() ?? 0 }}
            </div>
            <div>
                {{ $roles->onEachSide(1)->links() }}
            </div>
        </div>
    </div>

    <!-- Roles Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($roles as $role)
        <div class="pw-card bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-lg">{{ substr($role->name, 0, 2) }}</span>
                </div>
                <div class="flex items-center gap-0.5">
                    <button onclick="editRole({{ $role->id }})" class="pw-btn-icon-edit inline-flex items-center justify-center rounded-lg" title="Editar">
                        <i data-lucide="edit"></i>
                    </button>
                    <button onclick="deleteRole({{ $role->id }})" class="pw-btn-icon-delete inline-flex items-center justify-center rounded-lg" title="Eliminar">
                        <i data-lucide="trash-2"></i>
                    </button>
                    <button onclick="toggleRoleStatus({{ $role->id }})" class="inline-flex items-center justify-center rounded-lg {{ $role->activo ? 'pw-btn-icon-toggle-active' : 'pw-btn-icon-toggle-inactive' }}" title="{{ $role->activo ? 'Desactivar' : 'Activar' }}">
                        <i data-lucide="{{ $role->activo ? 'toggle-right' : 'toggle-left' }}"></i>
                    </button>
                </div>
            </div>
            
            <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ $role->name }}</h3>
            <p class="text-gray-600 text-sm mb-4">{{ $role->description ?? 'Sin descripción' }}</p>
            
            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center text-gray-500">
                    <i data-lucide="users" class="w-4 h-4 mr-1"></i>
                    <span>{{ $role->users_count }} usuarios</span>
                </div>
                <div class="px-2 py-1 rounded-full text-xs {{ $role->activo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                    {{ $role->activo ? 'Activo' : 'Inactivo' }}
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $roles->onEachSide(1)->links() }}
    </div>

    @if($roles->isEmpty())
    <div class="text-center py-12">
        <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
            <i data-lucide="shield" class="w-8 h-8 text-gray-400"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No hay roles registrados</h3>
        <p class="text-gray-500 mb-4">Comienza creando tu primer rol para organizar los permisos</p>
        <button onclick="showCreateRoleModal()" class="pw-btn-primary px-4 py-2 rounded-lg">
            <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>
            Crear Primer Rol
        </button>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8 mb-4">
        <div class="pw-card bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-1">Top roles por usuarios asignados</h3>
            <p class="text-xs text-gray-500 mb-4">Hasta 10 roles con más usuarios (respeta filtros de búsqueda y estado).</p>
            <div class="h-64 relative">
                <canvas id="chartRolesUsersTop"></canvas>
            </div>
        </div>
        <div class="pw-card bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <h3 class="text-base font-semibold text-gray-900 mb-1">Estado de roles</h3>
            <p class="text-xs text-gray-500 mb-4">Activos vs inactivos en el alcance actual del listado.</p>
            <div class="h-64 relative max-w-sm mx-auto">
                <canvas id="chartRolesActivoPie"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Create Role Modal -->
<div id="createRoleModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="pw-modal-content bg-white rounded-xl p-6 w-full overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Crear Nuevo Rol</h3>
        <form action="{{ route('roles.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del Rol *</label>
                    <input type="text" name="name" required class="w-full pw-input">
                    @if(!empty($empresaPrefijo))
                    <p class="mt-1 text-xs text-gray-500">Se guardará con prefijo de empresa: <strong>{{ $empresaPrefijo }}-</strong></p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                    <textarea name="description" rows="3" class="w-full pw-textarea"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Permisos / Apartados que puede ver</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto text-sm">
                        @foreach($availablePermissions as $permKey => $permLabel)
                            <label class="inline-flex items-center gap-2 text-gray-700">
                                <input type="checkbox" name="permissions[]" value="{{ $permKey }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>{{ $permLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Solo se muestran los apartados disponibles en la empresa activa.</p>
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="hideCreateRoleModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg">
                    Crear Rol
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Role Modal -->
<div id="editRoleModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="pw-modal-content bg-white rounded-xl p-6 w-full overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Editar Rol</h3>
        <form id="editRoleForm" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del Rol *</label>
                    <input type="text" id="editNombre" name="name" required class="w-full pw-input">
                    @if(!empty($empresaPrefijo))
                    <p class="mt-1 text-xs text-gray-500">Se mantiene prefijo de empresa: <strong>{{ $empresaPrefijo }}-</strong></p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                    <textarea id="editDescripcion" name="description" rows="3" class="w-full pw-textarea"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Permisos / Apartados que puede ver</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto text-sm" id="editPermsContainer">
                        @foreach($availablePermissions as $permKey => $permLabel)
                            <label class="inline-flex items-center gap-2 text-gray-700">
                                <input type="checkbox" name="permissions[]" value="{{ $permKey }}" class="edit-perm-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>{{ $permLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Solo se muestran los apartados disponibles en la empresa activa.</p>
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="hideEditRoleModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg">
                    Actualizar Rol
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" defer></script>
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
        const topSeries = @json($chartTopUsersByRole ?? []);
        const elTop = document.getElementById('chartRolesUsersTop');
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
                            return 'rgba(99, 102, 241, ' + a + ')';
                        }),
                        borderColor: '#4f46e5',
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
        const elPie = document.getElementById('chartRolesActivoPie');
        const act = {{ (int) ($stats['roles_active'] ?? 0) }};
        const inact = {{ (int) ($stats['roles_inactive'] ?? 0) }};
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
function rolesApiUrl(id, suffix) { return resourceApiUrl('/roles', id, suffix); }
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}
async function parseJsonResponse(response) {
    const ct = response.headers.get('content-type') || '';
    if (ct.includes('application/json')) return await response.json().catch(() => null);
    return null;
}

function showCreateRoleModal() {
    document.getElementById('createRoleModal').classList.remove('hidden');
}

function hideCreateRoleModal() {
    document.getElementById('createRoleModal').classList.add('hidden');
}

function showEditRoleModal() {
    document.getElementById('editRoleModal').classList.remove('hidden');
}

function hideEditRoleModal() {
    document.getElementById('editRoleModal').classList.add('hidden');
}

function editRole(id) {
    fetch(rolesApiUrl(id, '/edit'), {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(async (response) => {
            const data = await parseJsonResponse(response);
            if (!response.ok || !data) throw new Error(data?.message || 'No se pudo cargar el rol.');
            return data;
        })
        .then(data => {
            document.getElementById('editNombre').value = data.name;
            document.getElementById('editDescripcion').value = data.description || '';
            document.getElementById('editRoleForm').action = rolesApiUrl(id);

            const perms = Array.isArray(data.permissions) ? data.permissions : [];
            document.querySelectorAll('#editPermsContainer .edit-perm-checkbox').forEach(cb => {
                cb.checked = perms.includes(cb.value);
            });

            showEditRoleModal();
        })
        .catch(error => alert(error?.message || 'Error al cargar el rol.'));
}

function deleteRole(id) {
    var doDelete = function() {
        fetch(rolesApiUrl(id), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(async (response) => {
            const data = await parseJsonResponse(response);
            if (!response.ok || !data?.success) {
                throw new Error(data?.message || 'No se pudo eliminar el rol.');
            }
            return data;
        })
        .then(data => {
            if (typeof showNotification === 'function') showNotification(data.message || 'Rol eliminado correctamente.', 'success');
            location.reload();
        })
        .catch(error => {
            if (typeof showNotification === 'function') showNotification(error?.message || 'Error al eliminar.', 'error');
            else alert(error?.message || 'Error al eliminar el rol.');
        });
    };
    if (typeof showConfirmModal === 'function') {
        showConfirmModal({
            title: 'Eliminar rol',
            message: '¿Estás seguro de que deseas eliminar este rol? Esta acción no se puede deshacer.',
            confirmText: 'Sí, eliminar',
            cancelText: 'Cancelar',
            danger: true,
            onConfirm: doDelete
        });
    } else if (confirm('¿Estás seguro de eliminar este rol?')) {
        doDelete();
    }
}

function toggleRoleStatus(id) {
    fetch(rolesApiUrl(id, '/toggle-status'), {
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
