@extends('layouts.admin-layout')

@section('title', 'Asignar - SAMS')
@section('header-title', 'Asignar equipos')
@section('header-subtitle', 'Asignar equipos a usuarios. Un equipo solo puede estar asignado a una persona.')

@section('content')
<div class="max-w-7xl mx-auto space-y-8">
    {{-- Filtros --}}
    <div class="pw-card rounded-2xl p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <i data-lucide="filter" class="w-5 h-5 text-teal-600"></i>
            Filtros
        </h3>
        <div class="flex flex-wrap gap-4 items-end">
            <div class="min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Empresa (equipos)</label>
                <select id="filter-empresa" class="pw-select w-full">
                    <option value="">Todas</option>
                    @foreach($empresas as $emp)
                    <option value="{{ $emp->id }}" {{ (string)$empresaId === (string)$emp->id ? 'selected' : '' }}>{{ $emp->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[220px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Buscar usuarios (selector)</label>
                <input type="text" id="filter-users" class="pw-input w-full" placeholder="Nombre o email..." value="{{ $q }}">
            </div>
            <div class="min-w-[220px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Buscar equipos (disponibles)</label>
                <input type="text" id="filter-equipos" class="pw-input w-full" placeholder="Nombre, descripción o código...">
            </div>
            <button type="button" id="btn-aplicar" class="pw-btn-primary px-4 py-2 rounded-lg inline-flex items-center gap-2">
                <i data-lucide="search" class="w-4 h-4"></i>
                Aplicar
            </button>
        </div>
    </div>

    {{-- Selección: usuario + equipos --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="pw-card rounded-2xl p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center gap-2">
                <i data-lucide="user" class="w-5 h-5 text-teal-600"></i>
                Seleccionar usuario
            </h3>
            <p class="text-sm text-gray-500 mb-4">Elige un usuario (foto y nombre) para asignarle equipos.</p>
            <div id="users-container" class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-80 overflow-y-auto">
                <p class="col-span-full text-gray-500 text-sm">Usa el filtro y pulsa Aplicar para cargar usuarios.</p>
            </div>
        </div>
        <div class="pw-card rounded-2xl p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center gap-2">
                <i data-lucide="package" class="w-5 h-5 text-teal-600"></i>
                Seleccionar equipos (disponibles)
            </h3>
            <p class="text-sm text-gray-500 mb-4">Solo se muestran equipos no asignados. Puedes elegir varios.</p>
            <div id="equipos-container" class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-80 overflow-y-auto">
                <p class="col-span-full text-gray-500 text-sm">Selecciona empresa y pulsa Aplicar para cargar equipos.</p>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-4">
        <button type="button" id="btn-asignar" class="pw-btn-primary px-6 py-3 rounded-xl inline-flex items-center gap-2" disabled>
            <i data-lucide="user-plus" class="w-5 h-5"></i>
            Preparar formato y enviar solicitud
        </button>
        <span id="selection-summary" class="text-sm text-gray-500"></span>
    </div>

    <form id="form-preview-solicitud" method="POST" action="{{ route('asignar.preview') }}" class="hidden">
        @csrf
        <input type="hidden" name="to_user_id" id="preview_to_user_id">
        <div id="preview_equipo_ids_wrap"></div>
    </form>

    {{-- Listado: usuarios con sus equipos asignados (cards) --}}
    <div class="pw-card rounded-2xl p-6">
        <div class="flex flex-wrap items-end justify-between gap-4 mb-3">
            <div class="flex items-center gap-2">
                <i data-lucide="list" class="w-5 h-5 text-teal-600"></i>
                <h3 class="text-lg font-semibold text-gray-900">Usuarios con equipos asignados</h3>
            </div>
            <div class="w-full md:w-80">
                <label class="block text-xs font-medium text-gray-600 mb-1">Filtrar usuarios con equipos asignados</label>
                <input type="text" id="filter-asignaciones-users" class="pw-input w-full" placeholder="Nombre o email del usuario...">
            </div>
        </div>
        <div id="asignaciones-container" class="space-y-6">
            @forelse($usersWithEquipos as $u)
            <div class="border border-gray-200 rounded-xl p-4 bg-gray-50/50" data-user-id="{{ $u->id }}">
                <div class="flex items-center gap-4 mb-4">
                    <img src="{{ $u->photo ? asset('storage/' . $u->photo) : 'https://ui-avatars.com/api/?name=' . urlencode($u->name . ' ' . ($u->last_name ?? '')) }}" alt="" class="w-12 h-12 rounded-full object-cover border-2 border-teal-200">
                    <div>
                        <p class="font-semibold text-gray-900">{{ $u->name }} {{ $u->last_name ?? '' }}</p>
                        <p class="text-sm text-gray-500">{{ $u->email }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($u->equipoAsignaciones as $asig)
                    @php($eq = $asig->equipo)
                    <div class="bg-white rounded-lg border border-gray-200 p-3 flex items-start gap-3 shadow-sm" data-asignacion-id="{{ $asig->id }}">
                        <div class="flex-shrink-0 flex gap-1">
                            @foreach($eq->imagenes->take(2) as $img)
                            <img src="{{ asset('storage/' . $img->path) }}" alt="" class="w-12 h-12 rounded object-cover border border-gray-200">
                            @endforeach
                            @if($eq->imagenes->count() === 0)
                            <div class="w-12 h-12 rounded bg-gray-100 flex items-center justify-center text-gray-400">
                                <i data-lucide="image" class="w-6 h-6"></i>
                            </div>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-gray-900 truncate">{{ $eq->nombre }}</p>
                            <p class="text-xs text-gray-600 line-clamp-2">{{ $eq->descripcion ?? '—' }}</p>
                        </div>
                        <label class="inline-flex items-center gap-1 text-xs text-gray-600">
                            <input type="checkbox" class="chk-traspaso rounded border-gray-300" value="{{ $eq->id }}">
                            Traspasar
                        </label>
                        <button type="button" class="btn-quitar-asignacion pw-btn-icon-delete flex-shrink-0 inline-flex items-center justify-center rounded-lg" data-asignacion-id="{{ $asig->id }}" title="Quitar asignación">
                            <i data-lucide="trash-2"></i>
                        </button>
                    </div>
                    @endforeach
                </div>
            </div>
            @empty
            <p class="text-gray-500">Aún no hay equipos asignados. Usa los filtros arriba, elige un usuario y uno o más equipos, y pulsa Asignar.</p>
            @endforelse
        </div>
    </div>

    @if(($pendingForMe ?? collect())->count() > 0)
    <div class="pw-card rounded-2xl p-6">
        <div class="flex items-center gap-2 mb-4">
            <i data-lucide="file-check-2" class="w-5 h-5 text-emerald-600"></i>
            <h3 class="text-lg font-semibold text-gray-900">Solicitudes pendientes para ti</h3>
        </div>
        <div class="space-y-4">
            @foreach($pendingForMe as $sol)
            <div class="border border-amber-200 bg-amber-50/60 rounded-xl p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-gray-900">Solicitud #{{ $sol->id }}</p>
                        <p class="text-sm text-gray-600">Enviada por: {{ $sol->creador?->name }} {{ $sol->creador?->last_name }}</p>
                    </div>
                    <div class="text-xs text-gray-500">{{ $sol->created_at?->format('Y-m-d H:i') }}</div>
                </div>
                <div class="mt-3 text-sm text-gray-700">
                    <p class="font-medium mb-1">Equipos:</p>
                    <ul class="list-disc pl-5">
                        @foreach($sol->items as $item)
                        <li>{{ $item->equipo?->nombre }} ({{ $item->equipo?->codigo ?? 'sin código' }})</li>
                        @endforeach
                    </ul>
                </div>
                @if(!empty($sol->comentario_revision))
                <div class="mt-3 p-3 rounded-lg border border-red-200 bg-red-50 text-sm text-red-800">
                    <strong>Observación del administrador:</strong> {{ $sol->comentario_revision }}
                </div>
                @endif
                @if(!empty($sol->html_formulario))
                <details class="mt-3">
                    <summary class="cursor-pointer text-sm text-blue-700">Ver formulario diligenciado</summary>
                    <div class="mt-2 border border-gray-200 rounded-lg bg-white p-3 overflow-auto max-h-64">@safeHtml($sol->html_formulario)</div>
                </details>
                @endif
                <div class="mt-4 flex gap-2">
                    <a href="{{ route('asignar.solicitud.aceptar', $sol->id) }}" class="pw-btn-success px-3 py-2 rounded-lg text-sm inline-flex items-center">Aceptar con firma</a>
                    <form method="POST" action="{{ route('asignar.solicitud.responder', $sol->id) }}">
                        @csrf
                        <input type="hidden" name="accion" value="rechazar">
                        <button type="submit" class="pw-btn-danger px-3 py-2 rounded-lg text-sm">Rechazar</button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="pw-card rounded-2xl p-4">
        <a href="{{ route('asignar.mis-cosas') }}" class="inline-flex items-center gap-2 text-sm text-blue-700 hover:underline">
            <i data-lucide="package-check" class="w-4 h-4"></i>
            Ir a Mis cosas (Mis equipos y Mis actas)
        </a>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    let selectedUserId = null;
    let selectedEquipoIds = new Set();

    const usersContainer = document.getElementById('users-container');
    const equiposContainer = document.getElementById('equipos-container');
    const btnAsignar = document.getElementById('btn-asignar');
    const selectionSummary = document.getElementById('selection-summary');
    const filterEmpresa = document.getElementById('filter-empresa');
    const filterUsers = document.getElementById('filter-users');
    const filterEquipos = document.getElementById('filter-equipos');
    const filterAsignacionesUsers = document.getElementById('filter-asignaciones-users');
    const asignacionesContainer = document.getElementById('asignaciones-container');

    function loadUsers() {
        const q = (filterUsers?.value || '').trim();
        const params = new URLSearchParams();
        if (q) params.set('q', q);
        fetch('{{ route("asignar.users") }}?' + params.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                const users = data.users || [];
                usersContainer.innerHTML = users.length === 0
                    ? '<p class="col-span-full text-gray-500 text-sm">No hay usuarios que coincidan.</p>'
                    : users.map(u => `
                        <button type="button" class="user-card flex items-center gap-3 p-3 rounded-xl border-2 text-left transition-all ${selectedUserId === u.id ? 'border-teal-500 bg-teal-50' : 'border-gray-200 hover:border-teal-300 hover:bg-gray-50'}" data-user-id="${u.id}">
                            <img src="${u.photo_url || ('https://ui-avatars.com/api/?name=' + encodeURIComponent(u.full_name || u.name))}" alt="" class="w-10 h-10 rounded-full object-cover border border-gray-200">
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-gray-900 truncate">${pwEscapeHtml(u.full_name || (u.name + ' ' + (u.last_name || '')))}</p>
                                <p class="text-xs text-gray-500 truncate">${pwEscapeHtml(u.email || '')}</p>
                            </div>
                        </button>
                    `).join('');
                usersContainer.querySelectorAll('.user-card').forEach(el => {
                    el.addEventListener('click', () => {
                        selectedUserId = parseInt(el.dataset.userId, 10);
                        usersContainer.querySelectorAll('.user-card').forEach(c => {
                            c.classList.remove('border-teal-500', 'bg-teal-50');
                            c.classList.add('border-gray-200');
                        });
                        el.classList.add('border-teal-500', 'bg-teal-50');
                        el.classList.remove('border-gray-200');
                        updateSummary();
                    });
                });
                if (typeof lucide !== 'undefined') lucide.createIcons();
            })
            .catch(() => { usersContainer.innerHTML = '<p class="col-span-full text-red-500 text-sm">Error al cargar usuarios.</p>'; });
    }

    function loadEquipos() {
        const empresaId = filterEmpresa?.value || '';
        const qEquipos = (filterEquipos?.value || '').trim();
        const params = new URLSearchParams();
        if (empresaId) params.set('empresa_id', empresaId);
        if (qEquipos) params.set('q', qEquipos);
        fetch('{{ route("asignar.equipos") }}?' + params.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                const equipos = data.equipos || [];
                equiposContainer.innerHTML = equipos.length === 0
                    ? '<p class="col-span-full text-gray-500 text-sm">No hay equipos disponibles (o ya están asignados).</p>'
                    : equipos.map(e => {
                        const imgs = (e.imagenes || []);
                        const img1 = imgs[0] ? pwEscapeHtml(imgs[0]) : '';
                        const img2 = imgs[1] ? pwEscapeHtml(imgs[1]) : '';
                        const checked = selectedEquipoIds.has(e.id);
                        return `
                        <button type="button" class="equipo-card flex items-start gap-3 p-3 rounded-xl border-2 text-left transition-all ${checked ? 'border-teal-500 bg-teal-50' : 'border-gray-200 hover:border-teal-300 hover:bg-gray-50'}" data-equipo-id="${e.id}">
                            <div class="flex-shrink-0 flex gap-1">
                                ${img1 ? `<img src="${img1}" alt="" class="w-10 h-10 rounded object-cover border border-gray-200">` : '<div class="w-10 h-10 rounded bg-gray-100 flex items-center justify-center text-gray-400"><i data-lucide="image" class="w-5 h-5"></i></div>'}
                                ${img2 ? `<img src="${img2}" alt="" class="w-10 h-10 rounded object-cover border border-gray-200">` : ''}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-gray-900 truncate">${pwEscapeHtml(e.nombre || '')}</p>
                                <p class="text-xs text-gray-600 line-clamp-2">${pwEscapeHtml(e.descripcion || '—')}</p>
                            </div>
                        </button>
                    `;
                    }).join('');
                equiposContainer.querySelectorAll('.equipo-card').forEach(el => {
                    el.addEventListener('click', () => {
                        const id = parseInt(el.dataset.equipoId, 10);
                        if (selectedEquipoIds.has(id)) {
                            selectedEquipoIds.delete(id);
                            el.classList.remove('border-teal-500', 'bg-teal-50');
                            el.classList.add('border-gray-200');
                        } else {
                            selectedEquipoIds.add(id);
                            el.classList.add('border-teal-500', 'bg-teal-50');
                            el.classList.remove('border-gray-200');
                        }
                        updateSummary();
                    });
                });
                if (typeof lucide !== 'undefined') lucide.createIcons();
            })
            .catch(() => { equiposContainer.innerHTML = '<p class="col-span-full text-red-500 text-sm">Error al cargar equipos.</p>'; });
    }

    function selectedAssignedEquipoIds() {
        const ids = new Set();
        document.querySelectorAll('.chk-traspaso:checked').forEach(chk => {
            ids.add(parseInt(chk.value, 10));
        });
        return ids;
    }

    function updateSummary() {
        const u = selectedUserId ? '1 usuario' : '0 usuarios';
        const e = selectedEquipoIds.size;
        const traspasos = selectedAssignedEquipoIds().size;
        const total = e + traspasos;
        selectionSummary.textContent = total > 0 && selectedUserId
            ? `Se enviará solicitud con ${total} equipo(s) al usuario seleccionado.`
            : (selectedUserId ? 'Selecciona al menos un equipo.' : 'Selecciona un usuario y uno o más equipos.');
        btnAsignar.disabled = !selectedUserId || total === 0;
    }

    document.getElementById('btn-aplicar')?.addEventListener('click', () => { loadUsers(); loadEquipos(); });
    filterUsers?.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); loadUsers(); loadEquipos(); } });
    filterEquipos?.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); loadEquipos(); } });

    function applyAsignacionesFilter() {
        const term = (filterAsignacionesUsers?.value || '').trim().toLowerCase();
        const cards = asignacionesContainer?.querySelectorAll('[data-user-id]') || [];
        cards.forEach(card => {
            if (!term) {
                card.classList.remove('hidden');
                return;
            }
            const name = (card.querySelector('p.font-semibold')?.textContent || '').toLowerCase();
            const email = (card.querySelector('p.text-sm')?.textContent || '').toLowerCase();
            const match = name.includes(term) || email.includes(term);
            card.classList.toggle('hidden', !match);
        });
    }

    filterAsignacionesUsers?.addEventListener('input', applyAsignacionesFilter);
    filterAsignacionesUsers?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            applyAsignacionesFilter();
        }
    });

    document.addEventListener('change', (e) => {
        if (e.target && e.target.classList.contains('chk-traspaso')) {
            updateSummary();
        }
    });

    btnAsignar?.addEventListener('click', () => {
        const assignedIds = selectedAssignedEquipoIds();
        const allIds = new Set([...selectedEquipoIds, ...assignedIds]);
        if (!selectedUserId || allIds.size === 0) return;

        const toUserInput = document.getElementById('preview_to_user_id');
        const wrap = document.getElementById('preview_equipo_ids_wrap');
        const form = document.getElementById('form-preview-solicitud');
        if (!toUserInput || !wrap || !form) return;

        toUserInput.value = String(selectedUserId);
        wrap.innerHTML = '';
        allIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'equipo_ids[]';
            input.value = String(id);
            wrap.appendChild(input);
        });
        form.submit();
    });

    asignacionesContainer?.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-quitar-asignacion');
        if (!btn) return;
        const id = btn.dataset.asignacionId;
        if (!id) return;
        if (typeof showConfirmModal !== 'function') {
            if (!confirm('¿Quitar esta asignación? El equipo quedará disponible para asignar a otro usuario.')) return;
        } else {
            showConfirmModal({
                title: 'Quitar asignación',
                message: '¿Estás seguro de que deseas quitar esta asignación? El equipo quedará disponible para asignar a otro usuario.',
                confirmText: 'Sí, quitar',
                cancelText: 'Cancelar',
                danger: true,
                onConfirm: function() { doQuitarAsignacion(id); }
            });
            return;
        }
        doQuitarAsignacion(id);
    });

    function doQuitarAsignacion(id) {
        fetch('{{ url("asignar") }}/' + id, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf } })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const card = document.querySelector('[data-asignacion-id="' + id + '"]');
                    if (card) card.remove();
                    if (typeof showNotification === 'function') showNotification('Asignación quitada correctamente.', 'success');
                } else {
                    if (typeof showNotification === 'function') showNotification(data.message || 'Error al quitar asignación.', 'error');
                    else alert(data.message || 'Error.');
                }
            })
            .catch(() => {
                if (typeof showNotification === 'function') showNotification('Error de conexión.', 'error');
                else alert('Error de conexión.');
            });
    }

    loadUsers();
    loadEquipos();
})();
</script>
@endsection
