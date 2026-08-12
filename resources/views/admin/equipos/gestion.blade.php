@extends('layouts.admin-layout')

@section('title', 'Gestión de Equipos - SAMS')
@section('header-title', 'Gestión de Equipos')
@section('header-subtitle', 'Administrar tipos y clases de equipos')

@section('header-actions')
<div class="flex flex-wrap items-center gap-2">
    <button onclick="showCreateTipoModal()" class="pw-btn-success px-4 py-2 rounded-lg">
        <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>
        Nuevo Tipo
    </button>
    <button onclick="showCreateClaseModal()" class="pw-btn-primary px-4 py-2 rounded-lg">
        <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>
        Nueva Clase
    </button>
</div>
@endsection

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
    </div>

    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <button onclick="showTab('tipos')" data-tab="tipos" class="py-4 px-1 border-b-2 border-indigo-500 font-medium text-sm text-indigo-600">
                Tipos de Equipos
            </button>
            <button onclick="showTab('clases')" data-tab="clases" class="py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                Clases de Equipos
            </button>
        </nav>
    </div>

    <!-- Tipos Tab -->
    <div id="tipos-tab" class="tab-content">
        <div class="pw-card bg-white shadow-md rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-3 w-10" aria-label="Expandir clases"></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nombre
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Alias
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Descripción
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Clases
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Estado
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($tiposEquipos as $tipo)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-4 whitespace-nowrap align-middle">
                                <button type="button" onclick="toggleExpandTipoClases({{ $tipo->id }})" class="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-800" title="Ver clases">
                                    <span id="chevron-tipo-wrap-{{ $tipo->id }}" class="inline-flex transition-transform duration-200">
                                        <i data-lucide="chevron-down" class="w-5 h-5"></i>
                                    </span>
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                        <span class="text-green-600 font-bold text-lg">{{ strtoupper(substr($tipo->nombre, 0, 1)) }}</span>
                                    </div>
                                    <div class="text-sm font-medium text-gray-900">{{ $tipo->nombre }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-700">{{ $tipo->alias ?: '—' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-600">{{ $tipo->descripcion ?? 'Sin descripción' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $tipo->clases->count() }} clases</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $tipo->activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $tipo->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('tipos.edit', $tipo) }}" class="pw-btn-icon-edit inline-flex items-center justify-center rounded-lg mr-1" title="Editar">
                                    <i data-lucide="edit-2"></i>
                                </a>
                                <button onclick="toggleTipoStatus({{ $tipo->id }})" class="inline-flex items-center justify-center rounded-lg mr-1 {{ $tipo->activo ? 'pw-btn-icon-toggle-active' : 'pw-btn-icon-toggle-inactive' }}" title="{{ $tipo->activo ? 'Desactivar' : 'Activar' }}">
                                    <i data-lucide="power"></i>
                                </button>
                                <button onclick="deleteTipo({{ $tipo->id }})" class="pw-btn-icon-delete inline-flex items-center justify-center rounded-lg" title="Eliminar">
                                    <i data-lucide="trash-2"></i>
                                </button>
                            </td>
                        </tr>
                        <tr id="expand-tipo-{{ $tipo->id }}" class="hidden bg-slate-50/90">
                            <td colspan="7" class="px-6 py-4 border-t border-gray-100">
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Clases de «{{ $tipo->nombre }}»</span>
                                    <button type="button" onclick="showCreateClaseModal({{ $tipo->id }})" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-lg pw-btn-primary" title="Nueva clase en este tipo">
                                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                        Clase
                                    </button>
                                </div>
                                @if($tipo->clases->isEmpty())
                                    <p class="text-sm text-gray-500">No hay clases. Use el botón + para crear la primera.</p>
                                @else
                                    <ul class="divide-y divide-gray-200 rounded-lg border border-gray-200 bg-white overflow-hidden">
                                        @foreach($tipo->clases as $clase)
                                        <li class="flex flex-wrap items-center gap-2 px-3 py-2 text-sm">
                                            <span class="font-medium text-gray-900 flex-1 min-w-[8rem]">{{ $clase->nombre }}</span>
                                            <span class="text-xs text-gray-500">{{ $clase->alias ?: '—' }}</span>
                                            <span class="text-xs text-gray-500">{{ (int) $clase->equipos_count }} eq.</span>
                                            <span class="px-2 py-0.5 text-xs rounded-full {{ $clase->activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ $clase->activo ? 'Activo' : 'Inactivo' }}</span>
                                            <span class="flex items-center gap-1 ml-auto">
                                                <a href="{{ route('clases.edit', $clase) }}" class="pw-btn-icon-edit inline-flex items-center justify-center rounded p-1.5" title="Editar"><i data-lucide="edit-2" class="w-4 h-4"></i></a>
                                                <button type="button" onclick="toggleClaseStatus({{ $clase->id }})" class="inline-flex items-center justify-center rounded p-1.5 {{ $clase->activo ? 'pw-btn-icon-toggle-active' : 'pw-btn-icon-toggle-inactive' }}" title="{{ $clase->activo ? 'Desactivar' : 'Activar' }}"><i data-lucide="power" class="w-4 h-4"></i></button>
                                                <button type="button" onclick="deleteClase({{ $clase->id }})" class="pw-btn-icon-delete inline-flex items-center justify-center rounded p-1.5" title="Eliminar"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                            </span>
                                        </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Clases Tab: compacto por tipo (desplegable) -->
    <div id="clases-tab" class="tab-content hidden space-y-2 max-w-4xl">
        @forelse($tiposEquipos as $tipo)
        <div class="pw-card rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center gap-2 px-3 py-2.5 sm:px-4">
                <button type="button" onclick="toggleClasesAccordion({{ $tipo->id }})" class="p-1 rounded-lg text-gray-500 hover:bg-gray-100 shrink-0" title="Desplegar clases" aria-expanded="false" id="acc-btn-{{ $tipo->id }}">
                    <span id="acc-chevron-wrap-{{ $tipo->id }}" class="inline-flex transition-transform duration-200">
                        <i data-lucide="chevron-down" class="w-5 h-5"></i>
                    </span>
                </button>
                <button type="button" onclick="toggleClasesAccordion({{ $tipo->id }})" class="flex-1 min-w-0 text-left">
                    <span class="font-semibold text-gray-900 truncate block">{{ $tipo->nombre }}</span>
                    @if($tipo->alias)
                        <span class="text-xs text-gray-500 truncate block">{{ $tipo->alias }}</span>
                    @endif
                </button>
                <span class="text-xs tabular-nums text-gray-500 shrink-0 bg-gray-100 px-2 py-0.5 rounded-full">{{ $tipo->clases->count() }} {{ $tipo->clases->count() === 1 ? 'clase' : 'clases' }}</span>
                <button type="button" onclick="event.stopPropagation(); showCreateClaseModal({{ $tipo->id }})" class="inline-flex items-center justify-center w-9 h-9 rounded-lg pw-btn-primary shrink-0" title="Nueva clase en este tipo">
                    <i data-lucide="plus" class="w-5 h-5"></i>
                </button>
            </div>
            <div id="acc-panel-{{ $tipo->id }}" class="hidden border-t border-gray-100 bg-slate-50/80">
                @forelse($tipo->clases as $clase)
                <div class="flex flex-wrap items-center gap-2 px-4 py-2.5 border-b border-gray-100 last:border-b-0 text-sm">
                    <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center shrink-0">
                        <span class="text-blue-700 font-bold text-sm">{{ strtoupper(substr($clase->nombre, 0, 1)) }}</span>
                    </div>
                    <div class="flex-1 min-w-[10rem]">
                        <div class="font-medium text-gray-900">{{ $clase->nombre }}</div>
                        <div class="text-xs text-gray-500 line-clamp-1">{{ $clase->descripcion ?: ($clase->alias ?: '—') }}</div>
                    </div>
                    <span class="text-xs text-gray-500">{{ (int) $clase->equipos_count }} equipos</span>
                    <span class="px-2 py-0.5 text-xs rounded-full {{ $clase->activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ $clase->activo ? 'Activo' : 'Inactivo' }}</span>
                    <span class="flex items-center gap-1 ml-auto">
                        <a href="{{ route('clases.edit', $clase) }}" class="pw-btn-icon-edit inline-flex items-center justify-center rounded-lg p-2" title="Editar"><i data-lucide="edit-2" class="w-4 h-4"></i></a>
                        <button type="button" onclick="toggleClaseStatus({{ $clase->id }})" class="inline-flex items-center justify-center rounded-lg p-2 {{ $clase->activo ? 'pw-btn-icon-toggle-active' : 'pw-btn-icon-toggle-inactive' }}" title="{{ $clase->activo ? 'Desactivar' : 'Activar' }}"><i data-lucide="power" class="w-4 h-4"></i></button>
                        <button type="button" onclick="deleteClase({{ $clase->id }})" class="pw-btn-icon-delete inline-flex items-center justify-center rounded-lg p-2" title="Eliminar"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    </span>
                </div>
                @empty
                <p class="px-4 py-4 text-sm text-gray-500">No hay clases en este tipo. Pulse + para agregar una.</p>
                @endforelse
            </div>
        </div>
        @empty
        <p class="text-sm text-gray-600 pw-card p-6 rounded-lg">No hay tipos de equipo. Cree un tipo primero.</p>
        @endforelse
    </div>
</div>

<!-- Create Tipo Modal -->
<div id="createTipoModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="pw-modal-content relative top-20 mx-auto p-5 border shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center pb-4">
            <h3 class="text-lg font-medium text-gray-900">Nuevo Tipo de Equipo</h3>
            <button onclick="closeCreateTipoModal()" class="text-gray-400 hover:text-gray-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="{{ route('tipos.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del Tipo *</label>
                <input type="text" name="nombre" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Alias (manual)</label>
                <input type="text" name="alias" placeholder="Ej: PPE, SEG" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                <textarea name="descripcion" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
            </div>
            <div class="flex items-center">
                <input type="checkbox" name="activo" id="tipo_activo" checked class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                <label for="tipo_activo" class="ml-2 block text-sm text-gray-700">Activo</label>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" onclick="closeCreateTipoModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="pw-btn-success px-4 py-2 rounded-lg">
                    Crear Tipo
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Create Clase Modal -->
<div id="createClaseModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="pw-modal-content relative top-20 mx-auto p-5 border shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center pb-4">
            <h3 class="text-lg font-medium text-gray-900">Nueva Clase de Equipo</h3>
            <button onclick="closeCreateClaseModal()" class="text-gray-400 hover:text-gray-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="{{ route('clases.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre de la Clase *</label>
                <input type="text" name="nombre" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Alias (manual)</label>
                <input type="text" name="alias" placeholder="Ej: ARN, ESL" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Equipo *</label>
                <select id="clase_tipo_equipo_id" name="tipo_equipo_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Seleccione un tipo</option>
                    @foreach($tiposEquipos as $tipo)
                    <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                <textarea name="descripcion" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            <div class="flex items-center">
                <input type="checkbox" name="activo" id="clase_activo" checked class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="clase_activo" class="ml-2 block text-sm text-gray-700">Activo</label>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" onclick="closeCreateClaseModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg">
                    Crear Clase
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
const SAMS_URL_TIPOS = @json(url('/tipos'));
const SAMS_URL_CLASES = @json(url('/clases'));

// Tab functionality
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.add('hidden');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('[data-tab]').forEach(button => {
        button.classList.remove('border-indigo-500', 'text-indigo-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab
    document.getElementById(tabName + '-tab').classList.remove('hidden');
    
    // Add active class to selected button
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('border-indigo-500', 'text-indigo-600');
    document.querySelector(`[data-tab="${tabName}"]`).classList.remove('border-transparent', 'text-gray-500');
    
    // Reinitialize icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

// Modal functions for Tipos
function showCreateTipoModal() {
    document.getElementById('createTipoModal').classList.remove('hidden');
}

function closeCreateTipoModal() {
    document.getElementById('createTipoModal').classList.add('hidden');
}

// Modal functions for Clases
function showCreateClaseModal(prefillTipoId) {
    const modal = document.getElementById('createClaseModal');
    const sel = document.getElementById('clase_tipo_equipo_id');
    if (sel) {
        sel.value = prefillTipoId ? String(prefillTipoId) : '';
    }
    modal.classList.remove('hidden');
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function closeCreateClaseModal() {
    document.getElementById('createClaseModal').classList.add('hidden');
    const sel = document.getElementById('clase_tipo_equipo_id');
    if (sel) {
        sel.value = '';
    }
}

function toggleClasesAccordion(tipoId) {
    const panel = document.getElementById('acc-panel-' + tipoId);
    const chevWrap = document.getElementById('acc-chevron-wrap-' + tipoId);
    const btn = document.getElementById('acc-btn-' + tipoId);
    if (!panel) return;
    const addedHidden = panel.classList.toggle('hidden');
    if (chevWrap) {
        chevWrap.classList.toggle('rotate-180', !addedHidden);
    }
    if (btn) {
        btn.setAttribute('aria-expanded', addedHidden ? 'false' : 'true');
    }
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function toggleExpandTipoClases(tipoId) {
    const row = document.getElementById('expand-tipo-' + tipoId);
    const chevWrap = document.getElementById('chevron-tipo-wrap-' + tipoId);
    if (!row) return;
    row.classList.toggle('hidden');
    if (chevWrap) {
        chevWrap.classList.toggle('rotate-180', !row.classList.contains('hidden'));
    }
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function samsNotify(message, type) {
    if (typeof showNotification === 'function') {
        showNotification(message, type || 'error');
    }
}

function samsConfirmAndFetch(options) {
    showConfirmModal({
        title: options.title || 'Confirmar',
        message: options.message,
        confirmText: options.confirmText || 'Aceptar',
        cancelText: 'Cancelar',
        danger: options.danger === true,
        onConfirm: function () {
            fetch(options.url, {
                method: options.method,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    samsNotify(options.errorMessage);
                }
            })
            .catch(function () {
                samsNotify(options.errorMessage);
            });
        }
    });
}

function toggleTipoStatus(tipoId) {
    samsConfirmAndFetch({
        title: 'Cambiar estado',
        message: '¿Estás seguro de cambiar el estado de este tipo?',
        url: SAMS_URL_TIPOS + '/' + tipoId + '/toggle-status',
        method: 'POST',
        errorMessage: 'Error al cambiar estado'
    });
}

function toggleClaseStatus(claseId) {
    samsConfirmAndFetch({
        title: 'Cambiar estado',
        message: '¿Estás seguro de cambiar el estado de esta clase?',
        url: SAMS_URL_CLASES + '/' + claseId + '/toggle-status',
        method: 'POST',
        errorMessage: 'Error al cambiar estado'
    });
}

function deleteTipo(tipoId) {
    samsConfirmAndFetch({
        title: 'Eliminar tipo',
        message: '¿Estás seguro de eliminar este tipo? Esta acción no se puede deshacer.',
        confirmText: 'Eliminar',
        danger: true,
        url: SAMS_URL_TIPOS + '/' + tipoId,
        method: 'DELETE',
        errorMessage: 'Error al eliminar tipo'
    });
}

function deleteClase(claseId) {
    samsConfirmAndFetch({
        title: 'Eliminar clase',
        message: '¿Estás seguro de eliminar esta clase? Esta acción no se puede deshacer.',
        confirmText: 'Eliminar',
        danger: true,
        url: SAMS_URL_CLASES + '/' + claseId,
        method: 'DELETE',
        errorMessage: 'Error al eliminar clase'
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>
@endsection
