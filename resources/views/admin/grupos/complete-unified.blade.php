@extends('layouts.admin-layout')

@section('title', 'Gestión de Grupos - SAMS')
@section('header-title', 'Gestión Completa de Grupos')
@section('header-subtitle', 'Administración de grupos y equipos de trabajo')

@section('header-actions')
<div class="flex flex-wrap items-center gap-2">
    <button onclick="showCreateModal()" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
        <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>
        Nuevo Grupo
    </button>
</div>
@endsection

@section('content')
<div>
    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <button onclick="showTab('todos')" data-tab="todos" class="py-4 px-1 border-b-2 border-indigo-500 font-medium text-sm text-indigo-600">
                Todos los Grupos
            </button>
            <button onclick="showTab('activos')" data-tab="activos" class="py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                Activos
            </button>
            <button onclick="showTab('inactivos')" data-tab="inactivos" class="py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                Inactivos
            </button>
        </nav>
    </div>

    <!-- Tab Content -->
    <div id="todos-tab" class="tab-content">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($grupos as $grupo)
            <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="layers" class="w-6 h-6 text-orange-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">{{ $grupo->name }}</h3>
                            <p class="text-sm text-gray-600">{{ $grupo->description ?? 'Sin descripción' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="px-2 py-1 {{ $grupo->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} text-xs rounded-full">
                            {{ $grupo->active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center space-x-2 text-sm text-gray-600">
                        <i data-lucide="users" class="w-4 h-4"></i>
                        <span>{{ $grupo->members_count ?? 0 }} miembros</span>
                    </div>
                    @if($grupo->leader_name)
                    <div class="flex items-center space-x-2 text-sm text-gray-600">
                        <i data-lucide="user" class="w-4 h-4"></i>
                        <span>Líder: {{ $grupo->leader_name }}</span>
                    </div>
                    @endif
                    <div class="flex items-center space-x-2 text-sm text-gray-600">
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                        <span>Creado: {{ $grupo->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>

                <div class="flex justify-between items-center pt-4 border-t border-gray-100">
                    <div class="flex space-x-2">
                        <button onclick="editGrupo({{ $grupo->id }})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg">
                            <i data-lucide="edit-2" class="w-4 h-4"></i>
                        </button>
                        <button onclick="toggleStatus({{ $grupo->id }})" class="p-2 text-yellow-600 hover:bg-yellow-50 rounded-lg">
                            <i data-lucide="power" class="w-4 h-4"></i>
                        </button>
                        <button onclick="addMember({{ $grupo->id }})" class="p-2 text-green-600 hover:bg-green-50 rounded-lg">
                            <i data-lucide="user-plus" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <div class="flex space-x-2">
                        <button onclick="deleteGrupo({{ $grupo->id }})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div id="activos-tab" class="tab-content hidden">
        <div class="text-center py-12">
            <i data-lucide="check-circle" class="w-16 h-16 text-green-500 mx-auto mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Grupos Activos</h3>
            <p class="text-gray-600">Mostrando solo grupos activos</p>
        </div>
    </div>

    <div id="inactivos-tab" class="tab-content hidden">
        <div class="text-center py-12">
            <i data-lucide="x-circle" class="w-16 h-16 text-red-500 mx-auto mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Grupos Inactivos</h3>
            <p class="text-gray-600">Mostrando solo grupos inactivos</p>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center pb-4">
            <h3 class="text-lg font-medium text-gray-900">Nuevo Grupo</h3>
            <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="{{ route('grupos.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del Grupo *</label>
                <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Líder del Grupo</label>
                <select name="leader_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <option value="">Selecciona un líder...</option>
                    @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }} {{ $user->last_name ?? '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" onclick="closeCreateModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                    Crear Grupo
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Tab functionality
function showTab(tabName) {
    try {
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
        const selectedTab = document.getElementById(tabName + '-tab');
        if (selectedTab) {
            selectedTab.classList.remove('hidden');
        }
        
        // Add active class to selected button
        const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
        if (activeButton) {
            activeButton.classList.add('border-indigo-500', 'text-indigo-600');
            activeButton.classList.remove('border-transparent', 'text-gray-500');
        }
    } catch (error) {
        console.error('Error showing tab:', error);
    }
}

// Modal functions
function showCreateModal() {
    try {
        const modal = document.getElementById('createModal');
        if (modal) {
            modal.classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error showing modal:', error);
    }
}

function closeCreateModal() {
    try {
        const modal = document.getElementById('createModal');
        if (modal) {
            modal.classList.add('hidden');
        }
    } catch (error) {
        console.error('Error closing modal:', error);
    }
}

// Action functions
function editGrupo(grupoId) {
    try {
        window.location.href = `/grupos/${grupoId}/edit`;
    } catch (error) {
        console.error('Error editing grupo:', error);
    }
}

function toggleStatus(grupoId) {
    try {
        if (confirm('¿Estás seguro de cambiar el estado de este grupo?')) {
            fetch(`/grupos/${grupoId}/toggle-status`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Grupo actualizado exitosamente');
                    location.reload();
                } else {
                    alert('Error al cambiar estado');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cambiar estado');
            });
        }
    } catch (error) {
        console.error('Error toggling status:', error);
        alert('Error al cambiar estado');
    }
}

function addMember(grupoId) {
    try {
        alert('Agregar miembro en desarrollo para grupo: ' + grupoId);
    } catch (error) {
        console.error('Error adding member:', error);
    }
}

function deleteGrupo(grupoId) {
    try {
        if (confirm('¿Estás seguro de eliminar este grupo? Esta acción no se puede deshacer.')) {
            fetch(`/grupos/${grupoId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Grupo eliminado exitosamente');
                    location.reload();
                } else {
                    alert('Error al eliminar grupo');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al eliminar grupo');
            });
        }
    } catch (error) {
        console.error('Error deleting grupo:', error);
        alert('Error al eliminar grupo');
    }
}
</script>
@endsection
