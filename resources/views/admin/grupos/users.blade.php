@extends('layouts.admin-layout')

@section('title', 'Usuarios del Grupo - SAMS')
@section('header-title', 'Usuarios del Grupo')
@section('header-subtitle')
<span>Grupo: {{ $grupo->name }}</span>
@if($grupo->leader)
<span class="ml-2">•</span>
<span class="ml-2">
    <i data-lucide="crown" class="w-4 h-4 inline mr-1 text-yellow-500"></i>
    Líder: {{ $grupo->leader->name }} {{ $grupo->leader->last_name ?? '' }}
</span>
@endif
@endsection

@section('header-actions')
<div class="flex flex-wrap items-center gap-2">
    <button onclick="window.history.back()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
        <i data-lucide="arrow-left" class="w-4 h-4 inline mr-2"></i>
        Volver
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
                    <h3 class="text-lg font-semibold text-gray-800">Total Usuarios</h3>
                    <p class="text-2xl font-bold text-blue-600">{{ $users->count() }}</p>
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
                    <p class="text-2xl font-bold text-green-600">{{ $users->where('active', true)->count() }}</p>
                </div>
            </div>
        </div>
        
        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
                    <i data-lucide="briefcase" class="w-6 h-6 text-white"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Cargos Diferentes</h3>
                    <p class="text-2xl font-bold text-purple-600">{{ $users->whereNotNull('cargo_id')->pluck('cargo_id')->unique()->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="pw-card bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Lista de Usuarios</h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cargo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center">
                                    <span class="text-white font-bold">{{ substr($user->name, 0, 1) }}{{ substr($user->last_name ?? '', 0, 1) }}</span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $user->name }} {{ $user->last_name ?? '' }}
                                        @if($grupo->leader_id == $user->id)
                                        <i data-lucide="crown" class="w-4 h-4 inline ml-1 text-yellow-500" title="Líder del Grupo"></i>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-500">ID: {{ $user->id }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $user->email }}</div>
                            @if($user->email_verified_at)
                            <div class="text-xs text-green-600">✓ Verificado</div>
                            @else
                            <div class="text-xs text-yellow-600">⚠ No verificado</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $user->role->name ?? 'Sin rol' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $user->cargo->name ?? 'Sin cargo' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $user->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $user->active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('users.edit', $user->id) }}" class="text-blue-600 hover:text-blue-900">
                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                </a>
                                <a href="mailto:{{ $user->email }}" class="text-green-600 hover:text-green-900">
                                    <i data-lucide="mail" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($users->isEmpty())
    <div class="text-center py-12">
        <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
            <i data-lucide="users" class="w-8 h-8 text-gray-400"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No hay usuarios en este grupo</h3>
        <p class="text-gray-500 mb-4">Este grupo aún no tiene usuarios asignados</p>
        <button onclick="window.history.back()" class="pw-btn-primary px-4 py-2 rounded-lg">
            <i data-lucide="arrow-left" class="w-4 h-4 inline mr-2"></i>
            Volver a Grupos
        </button>
    </div>
    @endif
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
</script>
@endsection
