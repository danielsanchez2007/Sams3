@extends('layouts.admin-layout')

@section('title', 'Gestión de Fabricantes - SAMS')
@section('header-title', 'Gestión de Fabricantes')
@section('header-subtitle', 'Administración completa de fabricantes del sistema')

@section('header-actions')
<div class="flex flex-wrap items-center gap-2">
    <button onclick="showCreateFabricanteModal()" class="pw-btn-success px-4 py-2 rounded-lg">
        <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>
        Nuevo Fabricante
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
                    <i data-lucide="factory" class="w-6 h-6 text-white"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Total Fabricantes</h3>
                    <p class="text-2xl font-bold text-blue-600">{{ $stats['total'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        
        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                    <i data-lucide="check-circle" class="w-6 h-6 text-white"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Activos</h3>
                    <p class="text-2xl font-bold text-green-600">{{ $stats['active'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        
        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
                    <i data-lucide="globe" class="w-6 h-6 text-white"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Con Contacto</h3>
                    <p class="text-2xl font-bold text-purple-600">{{ $stats['with_contact'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters + Pagination -->
    <div class="pw-card bg-white rounded-xl shadow-lg overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Lista de Fabricantes</h3>
                <form method="GET" data-auto-submit="1" data-auto-submit-debounce="700" class="flex flex-wrap items-end gap-2">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Buscar</label>
                        <input name="q" value="{{ $q ?? '' }}" type="text" placeholder="Nombre, contacto, email..." class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                        <a href="{{ route('fabricantes.complete') }}" class="pw-btn-secondary px-4 py-2 rounded-lg">Limpiar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="px-6 py-3 border-b border-gray-200 flex items-center justify-between">
            <div class="text-sm text-gray-600">
                Mostrando {{ $fabricantes->firstItem() ?? 0 }} - {{ $fabricantes->lastItem() ?? 0 }} de {{ $fabricantes->total() ?? 0 }}
            </div>
            <div>
                {{ $fabricantes->onEachSide(1)->links() }}
            </div>
        </div>
    </div>

    <!-- Fabricantes Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($fabricantes as $fabricante)
        <div class="pw-card bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-gradient-to-r from-pink-500 to-orange-500 rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-lg">{{ substr($fabricante->name, 0, 2) }}</span>
                </div>
                <div class="flex space-x-2">
                    <button onclick="editFabricante({{ $fabricante->id }})" class="pw-btn-icon-edit inline-flex items-center justify-center rounded-lg" title="Editar">
                        <i data-lucide="edit"></i>
                    </button>
                    <button onclick="deleteFabricante({{ $fabricante->id }})" class="pw-btn-icon-delete inline-flex items-center justify-center rounded-lg" title="Eliminar">
                        <i data-lucide="trash-2"></i>
                    </button>
                    <button onclick="toggleFabricanteStatus({{ $fabricante->id }})" class="inline-flex items-center justify-center rounded-lg {{ $fabricante->activo ? 'pw-btn-icon-toggle-active' : 'pw-btn-icon-toggle-inactive' }}" title="{{ $fabricante->activo ? 'Desactivar' : 'Activar' }}">
                        <i data-lucide="{{ $fabricante->activo ? 'toggle-right' : 'toggle-left' }}"></i>
                    </button>
                </div>
            </div>
            
            <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ $fabricante->name }}</h3>
            <p class="text-gray-600 text-sm mb-4">{{ $fabricante->description ?? 'Sin descripción' }}</p>
            
            @if($fabricante->email || $fabricante->telefono)
            <div class="space-y-2 mb-4">
                @if($fabricante->email)
                <div class="flex items-center text-sm text-gray-500">
                    <i data-lucide="mail" class="w-4 h-4 mr-2"></i>
                    <span>{{ $fabricante->email }}</span>
                </div>
                @endif
                @if($fabricante->telefono)
                <div class="flex items-center text-sm text-gray-500">
                    <i data-lucide="phone" class="w-4 h-4 mr-2"></i>
                    <span>{{ $fabricante->telefono }}</span>
                </div>
                @endif
            </div>
            @endif
            
            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center text-gray-500">
                    <i data-lucide="info" class="w-4 h-4 mr-1"></i>
                    <span>Fabricante</span>
                </div>
                <div class="px-2 py-1 rounded-full text-xs {{ $fabricante->activo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                    {{ $fabricante->activo ? 'Activo' : 'Inactivo' }}
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $fabricantes->onEachSide(1)->links() }}
    </div>

    @if($fabricantes->isEmpty())
    <div class="text-center py-12">
        <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
            <i data-lucide="factory" class="w-8 h-8 text-gray-400"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No hay fabricantes registrados</h3>
        <p class="text-gray-500 mb-4">Comienza registrando fabricantes para tus equipos</p>
        <button onclick="showCreateFabricanteModal()" class="pw-btn-primary px-4 py-2 rounded-lg">
            <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>
            Registrar Primer Fabricante
        </button>
    </div>
    @endif
</div>

<!-- Create Fabricante Modal -->
<div id="createFabricanteModal" class="fixed inset-0 hidden z-[11000] flex items-start md:items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="createFabricanteTitle">
    <div class="pw-modal-content pw-modal-md">
        <div class="pw-modal-header">
            <div class="pw-modal-header-main">
                <div class="pw-modal-header-icon" aria-hidden="true">
                    <i data-lucide="factory" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 id="createFabricanteTitle" class="pw-modal-title">Registrar fabricante</h3>
                    <p class="pw-modal-subtitle">Agrega un fabricante y sus datos de contacto.</p>
                </div>
            </div>
            <button type="button" class="pw-modal-close" onclick="hideCreateFabricanteModal()" aria-label="Cerrar">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form action="{{ route('fabricantes.store') }}" method="POST">
            @csrf
            <div class="pw-modal-body">
                <div class="space-y-4">
                    <div>
                        <label>Nombre del fabricante <span class="pw-req">*</span></label>
                        <input type="text" name="name" required class="w-full">
                    </div>
                    <div>
                        <label>Descripción</label>
                        <textarea name="description" rows="3" class="w-full"></textarea>
                    </div>
                    <div>
                        <label>Contacto</label>
                        <input type="text" name="contacto" class="w-full">
                    </div>
                    <div>
                        <label>Teléfono</label>
                        <input type="text" name="telefono" class="w-full">
                    </div>
                    <div>
                        <label>Email</label>
                        <input type="email" name="email" class="w-full">
                    </div>
                </div>
            </div>
            <div class="pw-modal-footer">
                <button type="button" onclick="hideCreateFabricanteModal()" class="pw-btn-secondary px-4 py-2.5 rounded-lg text-sm">
                    Cancelar
                </button>
                <button type="submit" class="pw-btn-primary px-4 py-2.5 rounded-lg text-sm">
                    Registrar fabricante
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Fabricante Modal -->
<div id="editFabricanteModal" class="fixed inset-0 hidden z-[11000] flex items-start md:items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="editFabricanteTitle">
    <div class="pw-modal-content pw-modal-md">
        <div class="pw-modal-header">
            <div class="pw-modal-header-main">
                <div class="pw-modal-header-icon" aria-hidden="true">
                    <i data-lucide="factory" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 id="editFabricanteTitle" class="pw-modal-title">Editar fabricante</h3>
                    <p class="pw-modal-subtitle">Actualiza la información de contacto del fabricante.</p>
                </div>
            </div>
            <button type="button" class="pw-modal-close" onclick="hideEditFabricanteModal()" aria-label="Cerrar">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form id="editFabricanteForm" method="POST">
            @csrf
            @method('PUT')
            <div class="pw-modal-body">
                <div class="space-y-4">
                    <div>
                        <label>Nombre del fabricante <span class="pw-req">*</span></label>
                        <input type="text" id="editNombre" name="name" required class="w-full">
                    </div>
                    <div>
                        <label>Descripción</label>
                        <textarea id="editDescripcion" name="description" rows="3" class="w-full"></textarea>
                    </div>
                    <div>
                        <label>Contacto</label>
                        <input type="text" id="editContacto" name="contacto" class="w-full">
                    </div>
                    <div>
                        <label>Teléfono</label>
                        <input type="text" id="editTelefono" name="telefono" class="w-full">
                    </div>
                    <div>
                        <label>Email</label>
                        <input type="email" id="editEmail" name="email" class="w-full">
                    </div>
                </div>
            </div>
            <div class="pw-modal-footer">
                <button type="button" onclick="hideEditFabricanteModal()" class="pw-btn-secondary px-4 py-2.5 rounded-lg text-sm">
                    Cancelar
                </button>
                <button type="submit" class="pw-btn-primary px-4 py-2.5 rounded-lg text-sm">
                    Actualizar fabricante
                </button>
            </div>
        </form>
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

function resourceApiUrl(marker, id, suffix) {
    const path = window.location.pathname || '';
    const idx = path.indexOf(marker);
    const base = idx >= 0 ? path.slice(0, idx + marker.length) : marker;
    return base + '/' + encodeURIComponent(String(id)) + (suffix || '');
}
function fabricantesApiUrl(id, suffix) { return resourceApiUrl('/fabricantes', id, suffix); }
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}
async function parseJsonResponse(response) {
    const ct = response.headers.get('content-type') || '';
    if (ct.includes('application/json')) return await response.json().catch(() => null);
    return null;
}

function showCreateFabricanteModal() {
    document.getElementById('createFabricanteModal').classList.remove('hidden');
}

function hideCreateFabricanteModal() {
    document.getElementById('createFabricanteModal').classList.add('hidden');
}

function showEditFabricanteModal() {
    document.getElementById('editFabricanteModal').classList.remove('hidden');
}

function hideEditFabricanteModal() {
    document.getElementById('editFabricanteModal').classList.add('hidden');
}

function editFabricante(id) {
    fetch(fabricantesApiUrl(id, '/edit'), {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(async (response) => {
            const data = await parseJsonResponse(response);
            if (!response.ok || !data) throw new Error(data?.message || 'No se pudo cargar el fabricante.');
            return data;
        })
        .then(data => {
            document.getElementById('editNombre').value = data.name;
            document.getElementById('editDescripcion').value = data.description || '';
            document.getElementById('editContacto').value = data.contacto || '';
            document.getElementById('editTelefono').value = data.telefono || '';
            document.getElementById('editEmail').value = data.email || '';
            document.getElementById('editFabricanteForm').action = fabricantesApiUrl(id);
            showEditFabricanteModal();
        })
        .catch(error => alert(error?.message || 'Error al cargar el fabricante.'));
}

function deleteFabricante(id) {
    if (!confirm('¿Estás seguro de eliminar este fabricante?')) return;
    fetch(fabricantesApiUrl(id), {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(async (response) => {
        const data = await parseJsonResponse(response);
        if (!response.ok || !data?.success) throw new Error(data?.message || 'No se pudo eliminar el fabricante.');
        return data;
    })
    .then(() => location.reload())
    .catch(error => alert(error?.message || 'Error al eliminar el fabricante.'));
}

function toggleFabricanteStatus(id) {
    fetch(fabricantesApiUrl(id, '/toggle-status'), {
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
