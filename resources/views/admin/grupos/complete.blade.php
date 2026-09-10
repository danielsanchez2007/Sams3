@extends('layouts.admin-layout')

@section('title', 'Gestión de Grupos - SAMS')
@section('header-title', 'Gestión de Grupos')
@section('header-subtitle', 'Administración completa de grupos del sistema')

@section('header-actions')
<div class="flex flex-wrap items-center gap-2">
    <button onclick="showCreateGrupoModal()" class="pw-btn-success px-4 py-2 rounded-lg">
        <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>
        Nuevo Grupo
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
                    <i data-lucide="users" class="w-6 h-6 text-white"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Total Grupos</h3>
                    <p class="text-2xl font-bold text-blue-600">{{ $stats['total_grupos'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        
        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                    <i data-lucide="user-check" class="w-6 h-6 text-white"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Usuarios Activos</h3>
                    <p class="text-2xl font-bold text-green-600">{{ $stats['active_users'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        
        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
                    <i data-lucide="users-2" class="w-6 h-6 text-white"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Promedio por Grupo</h3>
                    <p class="text-2xl font-bold text-purple-600">{{ $stats['avg_users_per_group'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters + Pagination -->
    <div class="pw-card bg-white rounded-xl shadow-lg overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Lista de Grupos</h3>
                <form method="GET" data-auto-submit="1" data-auto-submit-debounce="700" class="flex flex-wrap items-end gap-2">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Buscar</label>
                        <input name="q" value="{{ $q ?? '' }}" type="text" placeholder="Nombre, líder..." class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                        <a href="{{ route('grupos.complete') }}" class="pw-btn-secondary px-4 py-2 rounded-lg">Limpiar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="px-6 py-3 border-b border-gray-200 flex items-center justify-between">
            <div class="text-sm text-gray-600">
                Mostrando {{ $grupos->firstItem() ?? 0 }} - {{ $grupos->lastItem() ?? 0 }} de {{ $grupos->total() ?? 0 }}
            </div>
            <div>
                {{ $grupos->onEachSide(1)->links() }}
            </div>
        </div>
    </div>

    <!-- Groups Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($grupos as $grupo)
        <div class="pw-card bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-lg">{{ substr($grupo->name, 0, 2) }}</span>
                </div>
                <div class="flex space-x-2">
                    <button onclick="viewGrupoUsers({{ $grupo->id }})" class="pw-btn-icon-view inline-flex items-center justify-center rounded-lg" title="Ver Usuarios">
                        <i data-lucide="eye"></i>
                    </button>
                    <button onclick="editGrupo({{ $grupo->id }})" class="pw-btn-icon-edit inline-flex items-center justify-center rounded-lg" title="Editar">
                        <i data-lucide="edit"></i>
                    </button>
                    <button onclick="deleteGrupo({{ $grupo->id }})" class="pw-btn-icon-delete inline-flex items-center justify-center rounded-lg" title="Eliminar">
                        <i data-lucide="trash-2"></i>
                    </button>
                    <button onclick="toggleGrupoStatus({{ $grupo->id }})" class="inline-flex items-center justify-center rounded-lg {{ $grupo->activo ? 'pw-btn-icon-toggle-active' : 'pw-btn-icon-toggle-inactive' }}" title="{{ $grupo->activo ? 'Desactivar' : 'Activar' }}">
                        <i data-lucide="{{ $grupo->activo ? 'toggle-right' : 'toggle-left' }}"></i>
                    </button>
                </div>
            </div>
            
            <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ $grupo->name }}</h3>
            <p class="text-gray-600 text-sm mb-2">{{ $grupo->description ?? 'Sin descripción' }}</p>
            
            @if($grupo->leader)
            <div class="flex items-center text-sm text-gray-600 mb-2">
                <i data-lucide="crown" class="w-4 h-4 mr-1 text-yellow-500"></i>
                <span>Líder: {{ $grupo->leader->name }} {{ $grupo->leader->last_name ?? '' }}</span>
            </div>
            @endif
            
            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center text-gray-500">
                    <i data-lucide="users" class="w-4 h-4 mr-1"></i>
                    <span>{{ $grupo->users_count }} miembros</span>
                </div>
                <div class="px-2 py-1 rounded-full text-xs {{ $grupo->activo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                    {{ $grupo->activo ? 'Activo' : 'Inactivo' }}
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $grupos->onEachSide(1)->links() }}
    </div>

    @if($grupos->isEmpty())
    <div class="text-center py-12">
        <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
            <i data-lucide="users" class="w-8 h-8 text-gray-400"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No hay grupos registrados</h3>
        <p class="text-gray-500 mb-4">Comienza creando tu primer grupo para organizar a los usuarios</p>
        <button onclick="showCreateGrupoModal()" class="pw-btn-primary px-4 py-2 rounded-lg">
            <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>
            Crear Primer Grupo
        </button>
    </div>
    @endif
</div>

<!-- Create Grupo Modal -->
<div id="createGrupoModal" class="fixed inset-0 hidden z-[11000] flex items-start md:items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="createGrupoTitle">
    <div class="pw-modal-content pw-modal-md">
        <div class="pw-modal-header">
            <div class="pw-modal-header-main">
                <div class="pw-modal-header-icon" aria-hidden="true">
                    <i data-lucide="users" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 id="createGrupoTitle" class="pw-modal-title">Crear grupo</h3>
                    <p class="pw-modal-subtitle">Define el grupo, el líder y sus miembros.</p>
                </div>
            </div>
            <button type="button" class="pw-modal-close" onclick="hideCreateGrupoModal()" aria-label="Cerrar">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form action="{{ route('grupos.store') }}" method="POST">
            @csrf
            <div class="pw-modal-body">
                <div class="space-y-4">
                    <div>
                        <label>Nombre del grupo <span class="pw-req">*</span></label>
                        <input type="text" name="name" required class="w-full">
                    </div>
                    <div>
                        <label>Descripción</label>
                        <textarea name="description" rows="3" class="w-full"></textarea>
                    </div>
                    <div>
                        <label>Líder del grupo</label>
                        <select id="createLeader" name="leader_id" class="w-full">
                            <option value="">Seleccionar líder...</option>
                            @foreach($users as $user)
                            <option value="{{ $user->id }}" data-grupo-id="{{ $user->grupo_id }}" {{ isset($leaderIds) && $leaderIds->contains($user->id) ? 'hidden' : '' }}>{{ $user->name }} {{ $user->last_name ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Miembros del grupo</label>
                        <select id="createMembers" name="member_ids[]" multiple class="w-full min-h-[7rem]">
                            @foreach($users as $user)
                            <option value="{{ $user->id }}" data-grupo-id="{{ $user->grupo_id }}" {{ isset($leaderIds) && $leaderIds->contains($user->id) ? 'hidden' : '' }}>{{ $user->name }} {{ $user->last_name ?? '' }}</option>
                            @endforeach
                        </select>
                        <p class="pw-hint">Mantén Ctrl (o Cmd) para seleccionar varios miembros.</p>
                    </div>
                </div>
            </div>
            <div class="pw-modal-footer">
                <button type="button" onclick="hideCreateGrupoModal()" class="pw-btn-secondary px-4 py-2.5 rounded-lg text-sm">
                    Cancelar
                </button>
                <button type="submit" class="pw-btn-primary px-4 py-2.5 rounded-lg text-sm">
                    Crear grupo
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Grupo Modal -->
<div id="editGrupoModal" class="fixed inset-0 hidden z-[11000] flex items-start md:items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="editGrupoTitle">
    <div class="pw-modal-content pw-modal-md">
        <div class="pw-modal-header">
            <div class="pw-modal-header-main">
                <div class="pw-modal-header-icon" aria-hidden="true">
                    <i data-lucide="users" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 id="editGrupoTitle" class="pw-modal-title">Editar grupo</h3>
                    <p class="pw-modal-subtitle">Actualiza el líder y los miembros del grupo.</p>
                </div>
            </div>
            <button type="button" class="pw-modal-close" onclick="hideEditGrupoModal()" aria-label="Cerrar">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form id="editGrupoForm" method="POST">
            @csrf
            @method('PUT')
            <div class="pw-modal-body">
                <div class="space-y-4">
                    <div>
                        <label>Nombre del grupo <span class="pw-req">*</span></label>
                        <input type="text" id="editNombre" name="name" required class="w-full">
                    </div>
                    <div>
                        <label>Descripción</label>
                        <textarea id="editDescripcion" name="description" rows="3" class="w-full"></textarea>
                    </div>
                    <div>
                        <label>Líder del grupo</label>
                        <select id="editLeader" name="leader_id" class="w-full">
                            <option value="">Seleccionar líder...</option>
                            @foreach($users as $user)
                            <option value="{{ $user->id }}" data-grupo-id="{{ $user->grupo_id }}" {{ isset($leaderIds) && $leaderIds->contains($user->id) ? 'hidden' : '' }}>{{ $user->name }} {{ $user->last_name ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Miembros del grupo</label>
                        <select id="editMembers" name="member_ids[]" multiple class="w-full min-h-[7rem]">
                            @foreach($users as $user)
                            <option value="{{ $user->id }}" data-grupo-id="{{ $user->grupo_id }}" {{ isset($leaderIds) && $leaderIds->contains($user->id) ? 'hidden' : '' }}>{{ $user->name }} {{ $user->last_name ?? '' }}</option>
                            @endforeach
                        </select>
                        <p class="pw-hint">Mantén Ctrl (o Cmd) para seleccionar varios miembros.</p>
                    </div>
                </div>
            </div>
            <div class="pw-modal-footer">
                <button type="button" onclick="hideEditGrupoModal()" class="pw-btn-secondary px-4 py-2.5 rounded-lg text-sm">
                    Cancelar
                </button>
                <button type="submit" class="pw-btn-primary px-4 py-2.5 rounded-lg text-sm">
                    Actualizar grupo
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Grupo Users Modal -->
<div id="viewGrupoUsersModal" class="fixed inset-0 hidden z-[11000] flex items-start md:items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="viewGrupoUsersTitle">
    <div class="pw-modal-content pw-modal-md">
        <div class="pw-modal-header">
            <div class="pw-modal-header-main">
                <div class="pw-modal-header-icon" aria-hidden="true">
                    <i data-lucide="users" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="pw-modal-title" id="viewGrupoUsersTitle">Usuarios del grupo</h3>
                    <p class="pw-modal-subtitle" id="viewGrupoUsersDescription"></p>
                </div>
            </div>
            <button type="button" class="pw-modal-close" onclick="hideViewGrupoUsersModal()" aria-label="Cerrar">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <div class="pw-modal-body">
            <div class="mb-4" id="viewGrupoLeaderContainer"></div>
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <h4 class="text-sm font-semibold text-gray-800">Miembros</h4>
                </div>
                <div class="max-h-96 overflow-y-auto" id="viewGrupoUsersList"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Initialize Lucide icons
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});

function showCreateGrupoModal() {
    const createMembers = document.getElementById('createMembers');
    if (createMembers) {
        Array.from(createMembers.options).forEach(opt => {
            const gid = opt.getAttribute('data-grupo-id');
            const isAssigned = gid !== null && gid !== '' && gid !== 'null';
            opt.hidden = opt.hidden || isAssigned;
            if (opt.hidden) opt.selected = false;
        });
    }

    const createLeader = document.getElementById('createLeader');
    if (createLeader) {
        Array.from(createLeader.options).forEach(opt => {
            if (!opt.value) return;
            const gid = opt.getAttribute('data-grupo-id');
            const isAssigned = gid !== null && gid !== '' && gid !== 'null';
            opt.hidden = opt.hidden || isAssigned;
        });
    }
    document.getElementById('createGrupoModal').classList.remove('hidden');
}

function hideCreateGrupoModal() {
    document.getElementById('createGrupoModal').classList.add('hidden');
}

function showEditGrupoModal() {
    document.getElementById('editGrupoModal').classList.remove('hidden');
}

function hideEditGrupoModal() {
    document.getElementById('editGrupoModal').classList.add('hidden');
}

function resourceApiUrl(marker, id, suffix) {
    const path = window.location.pathname || '';
    const idx = path.indexOf(marker);
    const base = idx >= 0 ? path.slice(0, idx + marker.length) : marker;
    return base + '/' + encodeURIComponent(String(id)) + (suffix || '');
}
function gruposApiUrl(id, suffix) { return resourceApiUrl('/grupos', id, suffix); }
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}
async function parseJsonResponse(response) {
    const ct = response.headers.get('content-type') || '';
    if (ct.includes('application/json')) return await response.json().catch(() => null);
    return null;
}

function editGrupo(id) {
    fetch(gruposApiUrl(id, '/edit'), {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(async (response) => {
            const data = await parseJsonResponse(response);
            if (!response.ok || !data) throw new Error(data?.message || 'No se pudo cargar el grupo.');
            return data;
        })
        .then(data => {
            document.getElementById('editNombre').value = data.name;
            document.getElementById('editDescripcion').value = data.description || '';
            document.getElementById('editLeader').value = data.leader_id || '';

            const editLeader = document.getElementById('editLeader');
            if (editLeader && data.leader_id) {
                const leaderOpt = editLeader.querySelector(`option[value="${data.leader_id}"]`);
                if (leaderOpt) leaderOpt.hidden = false;
            }

            if (editLeader) {
                Array.from(editLeader.options).forEach(opt => {
                    if (!opt.value) return;
                    const gid = opt.getAttribute('data-grupo-id');
                    const isInThisGroup = String(gid) === String(id);
                    const isCurrentLeader = data.leader_id && String(opt.value) === String(data.leader_id);
                    opt.hidden = opt.hidden || (!isInThisGroup && !isCurrentLeader);
                });
            }

            const editMembers = document.getElementById('editMembers');
            const memberIds = Array.isArray(data.member_ids) ? data.member_ids.map(String) : [];
            Array.from(editMembers.options).forEach(opt => {
                const gid = opt.getAttribute('data-grupo-id');
                const canShow = gid === null || gid === '' || gid === 'null' || String(gid) === String(id);
                const isLeader = data.leader_id && String(opt.value) === String(data.leader_id);
                const isMember = memberIds.includes(String(opt.value));
                opt.hidden = !(canShow || isLeader || isMember) || opt.hidden;
                if (opt.hidden) {
                    opt.selected = false;
                } else {
                    opt.selected = isLeader || isMember;
                }
            });

            ensureLeaderMember('edit');
            document.getElementById('editGrupoForm').action = gruposApiUrl(id);
            showEditGrupoModal();
        })
        .catch(error => alert(error?.message || 'Error al cargar el grupo.'));
}

function ensureLeaderMember(prefix) {
    const leaderSel = document.getElementById(prefix + 'Leader');
    const membersSel = document.getElementById(prefix + 'Members');
    if (!leaderSel || !membersSel) return;

    const leaderId = leaderSel.value;
    if (!leaderId) return;

    const leaderOpt = Array.from(membersSel.options).find(o => String(o.value) === String(leaderId));
    if (leaderOpt) {
        leaderOpt.hidden = false;
        leaderOpt.selected = true;
        leaderOpt.dataset.fixedLeader = '1';
    }

    Array.from(membersSel.options).forEach(o => {
        if (String(o.value) !== String(leaderId)) {
            delete o.dataset.fixedLeader;
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const createLeader = document.getElementById('createLeader');
    const createMembers = document.getElementById('createMembers');
    if (createLeader && createMembers) {
        createLeader.addEventListener('change', () => ensureLeaderMember('create'));
        createMembers.addEventListener('change', () => ensureLeaderMember('create'));
    }

    const editLeader = document.getElementById('editLeader');
    const editMembers = document.getElementById('editMembers');
    if (editLeader && editMembers) {
        editLeader.addEventListener('change', () => ensureLeaderMember('edit'));
        editMembers.addEventListener('change', () => ensureLeaderMember('edit'));
    }
});

function viewGrupoUsers(id) {
    const pwEscapeHtml = window.pwEscapeHtml || function (value) {
        return String(value == null ? '' : value).replace(/[&<>"'`]/g, function (ch) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;', '`': '&#96;' })[ch];
        });
    };
    showViewGrupoUsersModal();
    document.getElementById('viewGrupoUsersList').innerHTML = '';
    document.getElementById('viewGrupoLeaderContainer').innerHTML = '';
    document.getElementById('viewGrupoUsersTitle').textContent = 'Usuarios del Grupo';
    document.getElementById('viewGrupoUsersDescription').textContent = 'Cargando...';

    fetch(gruposApiUrl(id, '/users'), {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(async (r) => {
            const data = await parseJsonResponse(r);
            if (!r.ok || !data) throw new Error('No se pudo cargar la información.');
            return data;
        })
        .then(data => {
            if (!data || !data.success) {
                document.getElementById('viewGrupoUsersDescription').textContent = 'No se pudo cargar la información.';
                return;
            }

            document.getElementById('viewGrupoUsersTitle').textContent = `Usuarios del Grupo: ${data.grupo?.name ?? ''}`;
            document.getElementById('viewGrupoUsersDescription').textContent = data.grupo?.description ?? '';

            const leader = data.leader;
            if (leader) {
                const leaderPhoto = leader.photo ? `{{ asset('storage') }}/${leader.photo}` : '';
                document.getElementById('viewGrupoLeaderContainer').innerHTML = `
                    <div class="flex items-center p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-200 flex items-center justify-center">
                            ${leaderPhoto ? `<img src="${leaderPhoto}" class="w-10 h-10 object-cover" />` : `<span class="text-gray-700 font-semibold">${(leader.name || 'L').substring(0,1)}</span>`}
                        </div>
                        <div class="ml-3">
                            <div class="text-sm font-semibold text-gray-900">${pwEscapeHtml(leader.name ?? '')} ${pwEscapeHtml(leader.last_name ?? '')}</div>
                            <div class="text-xs text-gray-600">Líder del grupo</div>
                        </div>
                    </div>
                `;
            }

            const list = document.getElementById('viewGrupoUsersList');
            const users = Array.isArray(data.users) ? data.users : [];
            if (users.length === 0) {
                list.innerHTML = `<div class="p-4 text-sm text-gray-600">No hay miembros asignados.</div>`;
                return;
            }

            list.innerHTML = users.map(u => {
                const photo = u.photo ? `{{ asset('storage') }}/${u.photo}` : '';
                return `
                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                        <div class="flex items-center">
                            <div class="w-9 h-9 rounded-full overflow-hidden bg-gray-200 flex items-center justify-center">
                                ${photo ? `<img src="${photo}" class="w-9 h-9 object-cover" />` : `<span class="text-gray-700 font-semibold">${(u.name || 'U').substring(0,1)}</span>`}
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900">${pwEscapeHtml(u.name ?? '')} ${pwEscapeHtml(u.last_name ?? '')}</div>
                            </div>
                        </div>
                        ${u.is_leader ? `<span class="text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-800">Líder</span>` : ''}
                    </div>
                `;
            }).join('');

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        })
        .catch(() => {
            document.getElementById('viewGrupoUsersDescription').textContent = 'Error al cargar la información.';
        });
}

function showViewGrupoUsersModal() {
    document.getElementById('viewGrupoUsersModal').classList.remove('hidden');
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function hideViewGrupoUsersModal() {
    document.getElementById('viewGrupoUsersModal').classList.add('hidden');
}

function deleteGrupo(id) {
    if (!confirm('¿Estás seguro de eliminar este grupo?')) return;
    fetch(gruposApiUrl(id), {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(async (response) => {
        const data = await parseJsonResponse(response);
        if (!response.ok || !data?.success) throw new Error(data?.message || 'No se pudo eliminar el grupo.');
        return data;
    })
    .then(() => location.reload())
    .catch(error => alert(error?.message || 'Error al eliminar el grupo.'));
}

function toggleGrupoStatus(id) {
    fetch(gruposApiUrl(id, '/toggle-status'), {
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
