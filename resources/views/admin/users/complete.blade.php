@extends('layouts.admin-layout')

@section('title', 'Gestión de Usuarios - SAMS')
@section('header-title', 'Gestión de Usuarios')
@section('header-subtitle', 'Administración completa de usuarios del sistema')

@section('header-actions')
@php
    $puedeCrearUsuarios = $canEditUsers ?? false;
@endphp
@if($puedeCrearUsuarios)
    <div class="flex flex-wrap items-center gap-2">
        <button onclick="showCreateUserModal()" class="pw-btn-success px-4 py-2 rounded-lg">
            <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>
            Nuevo Usuario
        </button>
    </div>
@endif
@endsection

@section('content')
<div class="max-w-7xl mx-auto">
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-300 bg-red-50 text-red-700 px-4 py-3 text-sm">
            <div class="font-semibold mb-1">No se pudo crear/actualizar el usuario:</div>
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-300 bg-green-50 text-green-700 px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-300 bg-red-50 text-red-700 px-4 py-3 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6 mb-8">
        <div class="pw-card bg-white rounded-xl shadow-lg p-4 md:p-6">
            <div class="flex items-center">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-500 rounded-lg flex items-center justify-center shrink-0">
                    <i data-lucide="users" class="w-5 h-5 md:w-6 md:h-6 text-white"></i>
                </div>
                <div class="ml-3 md:ml-4 min-w-0">
                    <h3 class="text-sm md:text-lg font-semibold text-gray-800 truncate">Total Usuarios</h3>
                    <p id="statTotalUsers" class="text-xl md:text-2xl font-bold text-blue-600">{{ $stats['total'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        
        <div class="pw-card bg-white rounded-xl shadow-lg p-4 md:p-6">
            <div class="flex items-center">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-green-500 rounded-lg flex items-center justify-center shrink-0">
                    <i data-lucide="user-check" class="w-5 h-5 md:w-6 md:h-6 text-white"></i>
                </div>
                <div class="ml-3 md:ml-4 min-w-0">
                    <h3 class="text-sm md:text-lg font-semibold text-gray-800 truncate">Activos</h3>
                    <p id="statActiveUsers" class="text-xl md:text-2xl font-bold text-green-600">{{ $stats['active'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        
        <div class="pw-card bg-white rounded-xl shadow-lg p-4 md:p-6">
            <div class="flex items-center">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-purple-500 rounded-lg flex items-center justify-center shrink-0">
                    <i data-lucide="shield" class="w-5 h-5 md:w-6 md:h-6 text-white"></i>
                </div>
                <div class="ml-3 md:ml-4 min-w-0">
                    <h3 class="text-sm md:text-lg font-semibold text-gray-800 truncate">Administradores</h3>
                    <p id="statAdminUsers" class="text-xl md:text-2xl font-bold text-purple-600">{{ $stats['admins'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        
        <div class="pw-card bg-white rounded-xl shadow-lg p-4 md:p-6">
            <div class="flex items-center">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-orange-500 rounded-lg flex items-center justify-center shrink-0">
                    <i data-lucide="mail" class="w-5 h-5 md:w-6 md:h-6 text-white"></i>
                </div>
                <div class="ml-3 md:ml-4 min-w-0">
                    <h3 class="text-sm md:text-lg font-semibold text-gray-800 truncate">Pendientes acceso</h3>
                    <p id="statPendingUsers" class="text-xl md:text-2xl font-bold text-orange-600">{{ $stats['pending'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="pw-card bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Lista de Usuarios</h3>
                <form method="GET" data-auto-submit="1" data-auto-submit-debounce="500" class="flex flex-wrap items-end gap-2">
                    <div class="w-full sm:w-auto">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Buscar</label>
                        <input name="q" value="{{ $q ?? '' }}" type="text" placeholder="Nombre, apellido, email..." class="w-full sm:w-auto px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Estado</label>
                        <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="all" {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}>Todos</option>
                            <option value="active" {{ ($status ?? 'all') === 'active' ? 'selected' : '' }}>Activos</option>
                            <option value="inactive" {{ ($status ?? 'all') === 'inactive' ? 'selected' : '' }}>Inactivos</option>
                            <option value="pending" {{ ($status ?? 'all') === 'pending' ? 'selected' : '' }}>Pendientes de acceso</option>
                        </select>
                    </div>

                    <div class="hidden sm:block">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Rol</label>
                        <select name="role_id" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="all" {{ ($roleId ?? 'all') === 'all' ? 'selected' : '' }}>Todos</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ (string)($roleId ?? 'all') === (string)$role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                            @endforeach
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
                        <a href="{{ route('users.complete') }}" class="pw-btn-secondary px-4 py-2 rounded-lg">Limpiar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="px-4 md:px-6 py-3 border-b border-gray-200 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-gray-600">
                Mostrando {{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }} de {{ $users->total() ?? 0 }}
            </div>
            <div class="overflow-x-auto">
                {{ $users->onEachSide(1)->links() }}
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full min-w-0">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                        <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="hidden sm:table-cell px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                        <th class="px-2 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-2 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($users as $user)
                    <tr class="hover:bg-gray-50" data-user-id="{{ $user->id }}" data-user-active="{{ $user->active ? 1 : 0 }}" data-user-is-admin="{{ (strtolower(trim((string)($user->role->name ?? ''))) === 'administrador') ? 1 : 0 }}" data-user-is-pending="{{ (!$user->active && $user->role_id === null) ? 1 : 0 }}">
                        <td class="px-3 sm:px-6 py-4">
                            <div class="flex items-center min-w-0">
                                @if($user->photo)
                                    <img src="{{ asset('storage/' . $user->photo) }}" alt="Avatar" class="w-9 h-9 sm:w-10 sm:h-10 rounded-full object-cover border border-gray-200 shrink-0">
                                @else
                                    <div class="w-9 h-9 sm:w-10 sm:h-10 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center shrink-0">
                                        <span class="text-white font-bold text-sm">{{ substr($user->name, 0, 1) }}{{ substr($user->last_name ?? '', 0, 1) }}</span>
                                    </div>
                                @endif
                                <div class="ml-3 min-w-0">
                                    <div class="text-sm font-medium text-gray-900 truncate">{{ $user->name }} {{ $user->last_name ?? '' }}</div>
                                    <div class="text-xs text-gray-500 md:hidden truncate">{{ $user->email }}</div>
                                    <div class="text-xs text-gray-400 hidden sm:block">{{ $user->codigo ? $user->codigo : 'ID: '.$user->id }}</div>
                                    <div class="text-xs text-gray-500 sm:hidden mt-0.5">{{ $user->role->name ?? 'Sin rol' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $user->email }}</div>
                            @if($user->email_verified_at)
                            <div class="text-xs text-green-600">✓ Verificado</div>
                            @else
                            <div class="text-xs text-yellow-600">⚠ No verificado</div>
                            @endif
                        </td>
                        <td class="hidden sm:table-cell px-4 sm:px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $user->role->name ?? 'Sin rol' }}</div>
                        </td>
                        <td class="px-2 sm:px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $user->active ? 'bg-green-100 text-green-800' : (($user->role_id === null) ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                {{ $user->active ? 'Activo' : (($user->role_id === null) ? 'Pendiente' : 'Inactivo') }}
                            </span>
                        </td>
                        <td class="px-2 sm:px-6 py-4 whitespace-nowrap text-sm font-medium">
                            @if($canEditUsers ?? true)
                            <div class="flex items-center gap-0.5">
                                <button type="button" onclick="editUser({{ $user->id }})" class="pw-btn-icon-edit inline-flex items-center justify-center rounded-lg" title="Editar">
                                    <i data-lucide="edit"></i>
                                </button>
                                <button type="button" onclick="toggleUserStatus({{ $user->id }})" class="inline-flex items-center justify-center rounded-lg {{ $user->active ? 'pw-btn-icon-toggle-active' : 'pw-btn-icon-toggle-inactive' }}" title="{{ $user->active ? 'Desactivar' : 'Activar' }}">
                                    <i data-lucide="{{ $user->active ? 'toggle-right' : 'toggle-left' }}"></i>
                                </button>
                                <button type="button" onclick="deleteUser({{ $user->id }})" class="pw-btn-icon-delete inline-flex items-center justify-center rounded-lg" title="Eliminar">
                                    <i data-lucide="trash-2"></i>
                                </button>
                            </div>
                            @else
                            <span class="text-gray-400 text-xs">Solo vista</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-4 md:px-6 py-3 border-t border-gray-200 flex items-center justify-end overflow-x-auto">
            {{ $users->onEachSide(1)->links() }}
        </div>
    </div>

    @if($users->isEmpty() && $puedeCrearUsuarios)
    <div class="text-center py-12">
        <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
            <i data-lucide="users" class="w-8 h-8 text-gray-400"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No hay usuarios registrados</h3>
        <p class="text-gray-500 mb-4">Comienza creando usuarios para el sistema</p>
        <button onclick="showCreateUserModal()" class="pw-btn-primary px-4 py-2 rounded-lg">
            <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>
            Crear Primer Usuario
        </button>
    </div>
    @endif
</div>

<!-- Create User Modal -->
<div id="createUserModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-3">
    <div class="pw-modal-content bg-white rounded-xl p-4 sm:p-6 w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Crear Nuevo Usuario</h3>
        <form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                    <input type="text" name="name" required value="{{ old('name') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Apellido</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Género</label>
                    <select name="gender" id="createGender" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="toggleGenderOther('create')">
                        <option value="">Seleccionar...</option>
                        <option value="hombre" {{ old('gender') === 'hombre' ? 'selected' : '' }}>Hombre</option>
                        <option value="mujer" {{ old('gender') === 'mujer' ? 'selected' : '' }}>Mujer</option>
                        <option value="otro" {{ old('gender') === 'otro' ? 'selected' : '' }}>Otro</option>
                    </select>
                </div>
                <div id="createGenderOtherWrap" class="{{ old('gender') === 'otro' ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-gray-700 mb-2">¿Cuál?</label>
                    <input type="text" id="createGenderOther" name="gender_other" value="{{ old('gender_other') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Escribe el género">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                    <input type="email" name="email" required value="{{ old('email') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo documento</label>
                    <select name="document_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccionar...</option>
                        <option value="CC" {{ old('document_type') === 'CC' ? 'selected' : '' }}>Cédula de Ciudadanía</option>
                        <option value="CE" {{ old('document_type') === 'CE' ? 'selected' : '' }}>Cédula de Extranjería</option>
                        <option value="TI" {{ old('document_type') === 'TI' ? 'selected' : '' }}>Tarjeta de Identidad</option>
                        <option value="PP" {{ old('document_type') === 'PP' ? 'selected' : '' }}>Pasaporte</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Número documento</label>
                    <input type="text" name="document_number" value="{{ old('document_number') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: 123456789">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de nacimiento</label>
                    <input type="date" name="birth_date" value="{{ old('birth_date') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="+573001112233">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
                    <input type="text" name="address" value="{{ old('address') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Calle, Ciudad, País">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">¿Correo corporativo?</label>
                    <select id="createHasCorporateEmail" name="has_corporate_email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="toggleCorporateEmail('create')">
                        <option value="0" {{ old('has_corporate_email', '0') == '0' ? 'selected' : '' }}>No</option>
                        <option value="1" {{ old('has_corporate_email') == '1' ? 'selected' : '' }}>Sí</option>
                    </select>
                </div>
                <div id="createCorporateEmailWrap" class="{{ old('has_corporate_email') == '1' ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Correo corporativo</label>
                    <input type="email" id="createCorporateEmail" name="corporate_email" value="{{ old('corporate_email') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="correo@empresa.com">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">¿Teléfono corporativo?</label>
                    <select id="createHasCorporatePhone" name="has_corporate_phone" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="toggleCorporatePhone('create')">
                        <option value="0" {{ old('has_corporate_phone', '0') == '0' ? 'selected' : '' }}>No</option>
                        <option value="1" {{ old('has_corporate_phone') == '1' ? 'selected' : '' }}>Sí</option>
                    </select>
                </div>
                <div id="createCorporatePhoneWrap" class="{{ old('has_corporate_phone') == '1' ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono corporativo</label>
                    <input type="text" id="createCorporatePhone" name="corporate_phone" value="{{ old('corporate_phone') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="+573001112233">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Contraseña *</label>
                    <input type="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="mt-1 text-xs text-gray-500">Contraseña provisional (mínimo 4). El usuario la cambiará al entrar.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirmar Contraseña *</label>
                    <input type="password" name="password_confirmation" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Foto (opcional)</label>
                    <input type="file" name="photo" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Firma (opcional)</label>
                    <input type="file" name="signature" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rol *</label>
                    <select name="role_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccionar rol</option>
                        @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ (string) old('role_id') === (string) $role->id ? 'selected' : '' }}>{{ $role->name }} - {{ $role->description ?? 'Sin descripción' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cargo</label>
                    <select name="cargo_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccionar cargo</option>
                        @foreach($cargos as $cargo)
                        <option value="{{ $cargo->id }}" {{ (string) old('cargo_id') === (string) $cargo->id ? 'selected' : '' }}>{{ $cargo->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Grupo</label>
                    <select name="grupo_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccionar grupo</option>
                        @foreach($grupos as $grupo)
                        <option value="{{ $grupo->id }}" {{ (string) old('grupo_id') === (string) $grupo->id ? 'selected' : '' }}>{{ $grupo->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                    <select name="active" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="1" {{ old('active', '1') == '1' ? 'selected' : '' }}>Activo</option>
                        <option value="0" {{ old('active') == '0' ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="hideCreateUserModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg">
                    Crear Usuario
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-3">
    <div class="pw-modal-content bg-white rounded-xl p-4 sm:p-6 w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Editar Usuario</h3>
        <form id="editUserForm" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                    <input type="text" id="editNombre" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Apellido</label>
                    <input type="text" id="editApellido" name="last_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Género</label>
                    <select id="editGender" name="gender" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="toggleGenderOther('edit')">
                        <option value="">Seleccionar...</option>
                        <option value="hombre">Hombre</option>
                        <option value="mujer">Mujer</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div id="editGenderOtherWrap" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">¿Cuál?</label>
                    <input type="text" id="editGenderOther" name="gender_other" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Escribe el género">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                    <input type="email" id="editEmail" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo documento</label>
                    <select id="editDocumentType" name="document_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccionar...</option>
                        <option value="CC">Cédula de Ciudadanía</option>
                        <option value="CE">Cédula de Extranjería</option>
                        <option value="TI">Tarjeta de Identidad</option>
                        <option value="PP">Pasaporte</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Número documento</label>
                    <input type="text" id="editDocumentNumber" name="document_number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: 123456789">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de nacimiento</label>
                    <input type="date" id="editBirthDate" name="birth_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                    <input type="tel" id="editPhone" name="phone" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="+573001112233">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
                    <input type="text" id="editAddress" name="address" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Calle, Ciudad, País">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">¿Correo corporativo?</label>
                    <select id="editHasCorporateEmail" name="has_corporate_email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="toggleCorporateEmail('edit')">
                        <option value="0">No</option>
                        <option value="1">Sí</option>
                    </select>
                </div>
                <div id="editCorporateEmailWrap" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Correo corporativo</label>
                    <input type="email" id="editCorporateEmail" name="corporate_email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="correo@empresa.com">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">¿Teléfono corporativo?</label>
                    <select id="editHasCorporatePhone" name="has_corporate_phone" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="toggleCorporatePhone('edit')">
                        <option value="0">No</option>
                        <option value="1">Sí</option>
                    </select>
                </div>
                <div id="editCorporatePhoneWrap" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono corporativo</label>
                    <input type="text" id="editCorporatePhone" name="corporate_phone" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="+573001112233">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nueva Contraseña</label>
                    <input type="password" id="editPassword" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Dejar en blanco para mantener actual">
                    <p class="mt-1 text-xs text-gray-500">Opcional. Mínimo 4 caracteres si se cambia.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirmar Contraseña</label>
                    <input type="password" id="editPasswordConfirmation" name="password_confirmation" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Dejar en blanco para mantener actual">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Foto (opcional)</label>
                    <input type="file" name="photo" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <div id="editPhotoPreviewWrap" class="mt-2 hidden">
                        <img id="editPhotoPreview" class="w-20 h-20 object-cover rounded-lg border" alt="Foto actual" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Firma (opcional)</label>
                    <input type="file" name="signature" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <div id="editSignaturePreviewWrap" class="mt-2 hidden">
                        <img id="editSignaturePreview" class="w-20 h-20 object-cover rounded-lg border" alt="Firma actual" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rol *</label>
                    <select id="editRol" name="role_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccionar rol</option>
                        @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }} - {{ $role->description ?? 'Sin descripción' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cargo</label>
                    <select id="editCargo" name="cargo_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccionar cargo</option>
                        @foreach($cargos as $cargo)
                        <option value="{{ $cargo->id }}">{{ $cargo->name }} - {{ $cargo->description ?? 'Sin descripción' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Grupo</label>
                    <select id="editGrupo" name="grupo_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccionar grupo</option>
                        @foreach($grupos as $grupo)
                        <option value="{{ $grupo->id }}">{{ $grupo->name }} - {{ $grupo->description ?? 'Sin descripción' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                    <select id="editActive" name="active" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="hideEditUserModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg">
                    Actualizar Usuario
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});

/** Base /users compatible con /public/... (Laragon) */
function usersApiUrl(id, suffix) {
    const path = window.location.pathname || '';
    const marker = '/users';
    const idx = path.indexOf(marker);
    const base = idx >= 0 ? path.slice(0, idx + marker.length) : marker;
    return base + '/' + encodeURIComponent(String(id)) + (suffix || '');
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function showCreateUserModal() {
    document.getElementById('createUserModal').classList.remove('hidden');
}

function hideCreateUserModal() {
    document.getElementById('createUserModal').classList.add('hidden');
}

function showEditUserModal() {
    document.getElementById('editUserModal').classList.remove('hidden');
}

function hideEditUserModal() {
    document.getElementById('editUserModal').classList.add('hidden');
}

function toggleGenderOther(prefix) {
    const gender = document.getElementById(prefix + 'Gender');
    const wrap = document.getElementById(prefix + 'GenderOtherWrap');
    const input = document.getElementById(prefix + 'GenderOther');
    if (!gender || !wrap || !input) return;

    if (gender.value === 'otro') {
        wrap.classList.remove('hidden');
    } else {
        wrap.classList.add('hidden');
        input.value = '';
    }
}

function toggleCorporateEmail(prefix) {
    const sel = document.getElementById(prefix + 'HasCorporateEmail');
    const wrap = document.getElementById(prefix + 'CorporateEmailWrap');
    const input = document.getElementById(prefix + 'CorporateEmail');
    if (!sel || !wrap || !input) return;

    if (sel.value === '1') {
        wrap.classList.remove('hidden');
    } else {
        wrap.classList.add('hidden');
        input.value = '';
    }
}

function toggleCorporatePhone(prefix) {
    const sel = document.getElementById(prefix + 'HasCorporatePhone');
    const wrap = document.getElementById(prefix + 'CorporatePhoneWrap');
    const input = document.getElementById(prefix + 'CorporatePhone');
    if (!sel || !wrap || !input) return;

    if (sel.value === '1') {
        wrap.classList.remove('hidden');
    } else {
        wrap.classList.add('hidden');
        input.value = '';
    }
}

function storageUrl(path) {
    if (!path) return '';
    const pathName = window.location.pathname || '';
    const idx = pathName.indexOf('/users');
    const root = idx >= 0 ? pathName.slice(0, idx) : '';
    return root + '/storage/' + String(path).replace(/^\/+/, '');
}

function setSelectValue(id, value) {
    const el = document.getElementById(id);
    if (!el) return;
    el.value = (value === null || value === undefined) ? '' : String(value);
}

async function parseJsonResponse(response) {
    const ct = response.headers.get('content-type') || '';
    if (ct.includes('application/json')) {
        return await response.json().catch(() => null);
    }
    return null;
}

function editUser(id) {
    fetch(usersApiUrl(id, '/edit'), {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(async (response) => {
            const data = await parseJsonResponse(response);
            if (!response.ok) {
                throw new Error(data?.message || `No se pudo cargar el usuario (${response.status})`);
            }
            if (!data) {
                throw new Error('Respuesta inválida al cargar el usuario.');
            }
            return data;
        })
        .then(data => {
            document.getElementById('editNombre').value = data.name || '';
            document.getElementById('editApellido').value = data.last_name || '';
            setSelectValue('editGender', data.gender || '');
            document.getElementById('editGenderOther').value = data.gender_other || '';
            document.getElementById('editEmail').value = data.email || '';
            setSelectValue('editDocumentType', data.document_type || '');
            document.getElementById('editDocumentNumber').value = data.document_number || '';
            document.getElementById('editBirthDate').value = data.birth_date || '';
            document.getElementById('editPhone').value = data.phone || '';
            document.getElementById('editAddress').value = data.address || '';
            setSelectValue('editHasCorporateEmail', data.has_corporate_email ? '1' : '0');
            document.getElementById('editCorporateEmail').value = data.corporate_email || '';
            setSelectValue('editHasCorporatePhone', data.has_corporate_phone ? '1' : '0');
            document.getElementById('editCorporatePhone').value = data.corporate_phone || '';
            setSelectValue('editRol', data.role_id || '');
            setSelectValue('editCargo', data.cargo_id || '');
            setSelectValue('editGrupo', data.grupo_id || '');
            setSelectValue('editActive', data.active ? '1' : '0');
            document.getElementById('editPassword').value = '';
            document.getElementById('editPasswordConfirmation').value = '';

            toggleGenderOther('edit');
            toggleCorporateEmail('edit');
            toggleCorporatePhone('edit');

            const photoWrap = document.getElementById('editPhotoPreviewWrap');
            const photo = document.getElementById('editPhotoPreview');
            if (data.photo) {
                photo.src = storageUrl(data.photo);
                photoWrap.classList.remove('hidden');
            } else {
                photo.src = '';
                photoWrap.classList.add('hidden');
            }

            const sigWrap = document.getElementById('editSignaturePreviewWrap');
            const sig = document.getElementById('editSignaturePreview');
            if (data.signature) {
                sig.src = storageUrl(data.signature);
                sigWrap.classList.remove('hidden');
            } else {
                sig.src = '';
                sigWrap.classList.add('hidden');
            }

            document.getElementById('editUserForm').action = usersApiUrl(id);
            showEditUserModal();
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        })
        .catch(error => alert(error?.message || 'Error al cargar el usuario.'));
}

function deleteUser(id) {
    if (!confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.')) {
        return;
    }

    fetch(usersApiUrl(id), {
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
            throw new Error(data?.message || `No se pudo eliminar el usuario (${response.status}).`);
        }
        return data;
    })
    .then(data => {
        const row = document.querySelector(`tr[data-user-id="${id}"]`);
        if (row) {
            const totalEl = document.getElementById('statTotalUsers');
            const activeEl = document.getElementById('statActiveUsers');
            const adminEl = document.getElementById('statAdminUsers');
            const pendingEl = document.getElementById('statPendingUsers');

            const dec = (el) => {
                if (!el) return;
                const n = parseInt((el.textContent || '0').trim(), 10);
                el.textContent = String(Math.max(0, (Number.isFinite(n) ? n : 0) - 1));
            };

            dec(totalEl);
            if (row.getAttribute('data-user-active') === '1') dec(activeEl);
            if (row.getAttribute('data-user-is-admin') === '1') dec(adminEl);
            if (row.getAttribute('data-user-is-pending') === '1') dec(pendingEl);

            row.remove();
        }
        alert(data.message || 'Usuario eliminado correctamente.');
    })
    .catch(error => alert(error?.message || 'Error al eliminar.'));
}

function toggleUserStatus(id) {
    fetch(usersApiUrl(id, '/toggle-status'), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(async (response) => {
        const data = await parseJsonResponse(response);
        if (!response.ok || !data?.success) {
            throw new Error(data?.message || `Error al cambiar el estado (${response.status})`);
        }
        return data;
    })
    .then((data) => {
        alert(data.message || 'Estado actualizado.');
        location.reload();
    })
    .catch(error => alert(error?.message || 'Error al cambiar el estado.'));
}
</script>
@endsection
