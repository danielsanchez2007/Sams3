<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - SAMS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeIn 0.5s ease-out; }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Background Pattern -->
    <div class="fixed inset-0 -z-10">
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 via-white to-purple-50"></div>
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%239C92AC" fill-opacity="0.03"%3E%3Cpath d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
    </div>

    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside id="sidebar" class="w-64 bg-white shadow-xl animate-slide-in relative">
            <!-- Close Button -->
            <button onclick="toggleSidebar()" class="absolute top-4 right-4 z-50 p-2 hover:bg-gray-100 rounded-lg transition-colors">
                <i data-lucide="x" class="w-5 h-5 text-gray-600"></i>
            </button>
            
            <div class="p-6">
                <div class="flex items-center space-x-3 mb-8">
                    <div class="w-10 h-10 gradient-bg rounded-lg flex items-center justify-center">
                        <i data-lucide="settings" class="w-6 h-6 text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">SAMS</h1>
                        <p class="text-xs text-gray-600">Panel Admin</p>
                    </div>
                </div>

                <!-- User Info -->
                <div class="mb-6 p-4 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <img src="{{ Auth::user()->photo ? asset('storage/' . Auth::user()->photo) : 'https://picsum.photos/seed/' . Auth::user()->id . '/40/40.jpg' }}" alt="Avatar" class="w-10 h-10 rounded-full border-2 border-indigo-500">
                        <div>
                            <p class="font-semibold text-gray-800">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-gray-600">Administrador</p>
                        </div>
                    </div>
                </div>

                <!-- Navigation Menu -->
                <nav class="space-y-2">
                    <!-- Gestión Principal - Desplegable -->
                    <div class="relative">
                        <button onclick="toggleSubmenu('gestion')" class="sidebar-item w-full flex items-center justify-between px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <i data-lucide="menu" class="w-5 h-5"></i>
                                <span class="font-semibold">Gestión Principal</span>
                            </div>
                            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="gestion-arrow"></i>
                        </button>
                        <div id="gestion-submenu" class="hidden pl-4 pr-4 py-2 space-y-1">
                            <!-- Usuarios -->
                            <div class="relative">
                                <button onclick="toggleSubmenu('usuarios')" class="sidebar-item w-full flex items-center justify-between px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <i data-lucide="users" class="w-5 h-5"></i>
                                        <span class="font-medium">Usuarios</span>
                                    </div>
                                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="usuarios-arrow"></i>
                                </button>
                                <div id="gestion-submenu" class="hidden pl-4 pr-4 py-2 space-y-1">
                            <!-- Usuarios - Direct Link -->
                            <a href="{{ route('users.complete') }}" class="sidebar-item w-full flex items-center px-4 py-3 text-indigo-600 hover:bg-indigo-50 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="users" class="w-5 h-5"></i>
                                    <span class="font-medium">Usuarios</span>
                                </div>
                            </a>

                            <!-- Roles -->
                            <div class="relative">
                                <button onclick="toggleSubmenu('roles')" class="sidebar-item w-full flex items-center justify-between px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <i data-lucide="shield" class="w-5 h-5"></i>
                                        <span class="font-medium">Roles</span>
                                    </div>
                                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="roles-arrow"></i>
                                </button>
                                <div id="roles-submenu" class="hidden pl-12 pr-4 py-2 space-y-1">
                                    <a href="#" class="block py-2 text-sm text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 px-3 rounded transition-all">📋 Lista de Roles</a>
                                    <a href="#" class="block py-2 text-sm text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 px-3 rounded transition-all">➕ Crear Rol</a>
                                    <a href="#" class="block py-2 text-sm text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 px-3 rounded transition-all">⚙️ Permisos</a>
                                </div>
                            </div>

                            <!-- Cargos -->
                            <div class="relative">
                                <button onclick="toggleSubmenu('cargos')" class="sidebar-item w-full flex items-center justify-between px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <i data-lucide="briefcase" class="w-5 h-5"></i>
                                        <span class="font-medium">Cargos</span>
                                    </div>
                                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="cargos-arrow"></i>
                                </button>
                                <div id="cargos-submenu" class="hidden pl-12 pr-4 py-2 space-y-1">
                                    <a href="#" class="block py-2 text-sm text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 px-3 rounded transition-all">📋 Lista de Cargos</a>
                                    <a href="#" class="block py-2 text-sm text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 px-3 rounded transition-all">➕ Nuevo Cargo</a>
                                </div>
                            </div>

                            <!-- Grupos -->
                            <div class="relative">
                                <button onclick="toggleSubmenu('grupos')" class="sidebar-item w-full flex items-center justify-between px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <i data-lucide="layers" class="w-5 h-5"></i>
                                        <span class="font-medium">Grupos</span>
                                    </div>
                                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="grupos-arrow"></i>
                                </button>
                                <div id="grupos-submenu" class="hidden pl-12 pr-4 py-2 space-y-1">
                                    <a href="#" class="block py-2 text-sm text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 px-3 rounded transition-all">📋 Lista de Grupos</a>
                                    <a href="#" class="block py-2 text-sm text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 px-3 rounded transition-all">➕ Crear Grupo</a>
                                    <a href="#" class="block py-2 text-sm text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 px-3 rounded transition-all">👥 Asignar Miembros</a>
                                </div>
                            </div>

                            <!-- Fabricantes -->
                            <div class="relative">
                                <button onclick="toggleSubmenu('fabricantes')" class="sidebar-item w-full flex items-center justify-between px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <i data-lucide="factory" class="w-5 h-5"></i>
                                        <span class="font-medium">Fabricantes</span>
                                    </div>
                                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="fabricantes-arrow"></i>
                                </button>
                                <div id="fabricantes-submenu" class="hidden pl-12 pr-4 py-2 space-y-1">
                                    <a href="#" class="block py-2 text-sm text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 px-3 rounded transition-all">� Lista de Fabricantes</a>
                                    <a href="#" class="block py-2 text-sm text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 px-3 rounded transition-all">➕ Nuevo Fabricante</a>
                                </div>
                            </div>
                        </div>
                            </div>

                            <!-- Roles -->
                            <div class="relative">
                                <button onclick="toggleSubmenu('roles')" class="sidebar-item w-full flex items-center justify-between px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <i data-lucide="shield" class="w-5 h-5"></i>
                                        <span class="font-medium">Roles</span>
                                    </div>
                                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="roles-arrow"></i>
                                </button>
                                <div id="roles-submenu" class="hidden pl-12 pr-4 py-2 space-y-1">
                                    <a href="#" class="block py-2 text-sm text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 px-3 rounded transition-all">📋 Lista de Roles</a>
                                    <a href="#" class="block py-2 text-sm text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 px-3 rounded transition-all">➕ Crear Rol</a>
                                    <a href="#" class="block py-2 text-sm text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 px-3 rounded transition-all">⚙️ Permisos</a>
                                </div>
                            </div>

                            <!-- Cargos -->
                            <div class="relative">
                                <button onclick="toggleSubmenu('cargos')" class="sidebar-item w-full flex items-center justify-between px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <i data-lucide="briefcase" class="w-5 h-5"></i>
                                        <span class="font-medium">Cargos</span>
                                    </div>
                                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="cargos-arrow"></i>
                                </button>
                                <div id="cargos-submenu" class="hidden pl-12 pr-4 py-2 space-y-1">
                                    <a href="#" class="block py-2 text-sm text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 px-3 rounded transition-all">📋 Lista de Cargos</a>
                                    <a href="#" class="block py-2 text-sm text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 px-3 rounded transition-all">➕ Nuevo Cargo</a>
                                </div>
                            </div>

                            <!-- Grupos -->
                            <div class="relative">
                                <button onclick="toggleSubmenu('grupos')" class="sidebar-item w-full flex items-center justify-between px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <i data-lucide="layers" class="w-5 h-5"></i>
                                        <span class="font-medium">Grupos</span>
                                    </div>
                                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="grupos-arrow"></i>
                                </button>
                                <div id="grupos-submenu" class="hidden pl-12 pr-4 py-2 space-y-1">
                                    <a href="#" class="block py-2 text-sm text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 px-3 rounded transition-all">📋 Lista de Grupos</a>
                                    <a href="#" class="block py-2 text-sm text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 px-3 rounded transition-all">➕ Crear Grupo</a>
                                    <a href="#" class="block py-2 text-sm text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 px-3 rounded transition-all">👥 Asignar Miembros</a>
                                </div>
                            </div>

                            <!-- Fabricantes -->
                            <div class="relative">
                                <button onclick="toggleSubmenu('fabricantes')" class="sidebar-item w-full flex items-center justify-between px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <i data-lucide="factory" class="w-5 h-5"></i>
                                        <span class="font-medium">Fabricantes</span>
                                    </div>
                                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="fabricantes-arrow"></i>
                                </button>
                                <div id="fabricantes-submenu" class="hidden pl-12 pr-4 py-2 space-y-1">
                                    <a href="#" class="block py-2 text-sm text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 px-3 rounded transition-all">📋 Lista de Fabricantes</a>
                                    <a href="#" class="block py-2 text-sm text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 px-3 rounded transition-all">➕ Nuevo Fabricante</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t pt-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Sistema</p>
                        
                        <a href="#" class="sidebar-item flex items-center space-x-3 px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                            <i data-lucide="settings" class="w-5 h-5"></i>
                            <span class="font-medium">Configuración</span>
                        </a>
                        
                        <a href="{{ route('logout') }}" class="sidebar-item flex items-center space-x-3 px-4 py-3 text-red-600 hover:text-red-700 rounded-lg">
                            <i data-lucide="log-out" class="w-5 h-5"></i>
                            <span class="font-medium">Cerrar Sesión</span>
                        </a>
                    </div>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Header -->
            <header class="glass-effect shadow-sm border-b border-gray-200">
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800">Gestión de Usuarios</h2>
                            <p class="text-gray-600">Administra todos los usuarios del sistema</p>
                        </div>
                        
                        <div class="flex items-center space-x-4">
                            <a href="{{ route('users.create') }}" class="pw-btn-primary px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors">
                                <i data-lucide="user-plus" class="w-5 h-5"></i>
                                <span>Nuevo Usuario</span>
                            </a>
                            
                            <button onclick="toggleSidebar()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                                <i data-lucide="menu" class="w-5 h-5 text-gray-600"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-6">
                <div class="animate-fade-in">
                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div class="pw-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-blue-100 rounded-lg">
                                    <i data-lucide="users" class="w-6 h-6 text-blue-600"></i>
                                </div>
                                <span class="text-sm font-medium text-green-600 bg-green-100 px-3 py-1 rounded-full">Total</span>
                            </div>
                            <h3 id="statTotalUsers" class="text-2xl font-bold text-gray-800">{{ $users->count() }}</h3>
                            <p class="text-gray-600 mt-2">Usuarios Totales</p>
                        </div>

                        <div class="pw-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-green-100 rounded-lg">
                                    <i data-lucide="user-check" class="w-6 h-6 text-green-600"></i>
                                </div>
                                <span class="text-sm font-medium text-blue-600 bg-blue-100 px-3 py-1 rounded-full">Activos</span>
                            </div>
                            <h3 id="statActiveUsers" class="text-2xl font-bold text-gray-800">{{ $users->where('active', true)->count() }}</h3>
                            <p class="text-gray-600 mt-2">Usuarios Activos</p>
                        </div>

                        <div class="pw-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-yellow-100 rounded-lg">
                                    <i data-lucide="user-x" class="w-6 h-6 text-yellow-600"></i>
                                </div>
                                <span class="text-sm font-medium text-purple-600 bg-purple-100 px-3 py-1 rounded-full">Inactivos</span>
                            </div>
                            <h3 id="statInactiveUsers" class="text-2xl font-bold text-gray-800">{{ $users->where('active', false)->count() }}</h3>
                            <p class="text-gray-600 mt-2">Usuarios Inactivos</p>
                        </div>

                        <div class="pw-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-purple-100 rounded-lg">
                                    <i data-lucide="calendar" class="w-6 h-6 text-purple-600"></i>
                                </div>
                                <span class="text-sm font-medium text-red-600 bg-red-100 px-3 py-1 rounded-full">Hoy</span>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-800">{{ $users->where('created_at', '>=', now()->startOfDay())->count() }}</h3>
                            <p class="text-gray-600 mt-2">Creados Hoy</p>
                        </div>
                    </div>

                    <!-- Users Table -->
                    <div class="pw-card bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xl font-semibold text-gray-800">Lista de Usuarios</h3>
                                <div class="flex items-center space-x-2">
                                    <input type="text" placeholder="Buscar usuarios..." class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <button class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                                        <i data-lucide="search" class="w-5 h-5 text-gray-600"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <form id="bulkDeleteForm" action="{{ route('users.bulk-destroy') }}" method="POST">
                                @csrf
                                @method('DELETE')

                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <input id="selectAllUsers" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Documento</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contacto</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol/Cargo</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($users as $user)
                                    <tr class="hover:bg-gray-50 transition-colors" data-user-id="{{ $user->id }}" data-user-active="{{ $user->active ? 1 : 0 }}">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox" class="userRowCheckbox h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" name="ids[]" value="{{ $user->id }}" data-user-active="{{ $user->active ? 1 : 0 }}">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <img class="h-10 w-10 rounded-full" src="{{ $user->photo ? asset('storage/' . $user->photo) : 'https://picsum.photos/seed/' . $user->id . '/40/40.jpg' }}" alt="">
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">{{ $user->name }} {{ $user->last_name }}</div>
                                                    <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $user->document_type }}: {{ $user->document_number }}</div>
                                            @if($user->birth_date)
                                            <div class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($user->birth_date)->age }} años</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $user->phone }}</div>
                                            @if($user->has_corporate_email && $user->corporate_email)
                                            <div class="text-sm text-gray-500">{{ $user->corporate_email }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $user->role?->name ?? 'Sin rol' }}</div>
                                            <div class="text-sm text-gray-500">{{ $user->cargo?->name ?? 'Sin cargo' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($user->active)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Activo</span>
                                            @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center space-x-2">
                                                <a href="{{ route('users.edit', $user) }}" class="text-indigo-600 hover:text-indigo-900">
                                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                                </a>
                                                <form action="{{ route('users.toggle-status', $user) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-yellow-600 hover:text-yellow-900">
                                                        <i data-lucide="{{ $user->active ? 'user-x' : 'user-check' }}" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                                <button onclick="resetPassword({{ $user->id }})" class="text-blue-600 hover:text-blue-900">
                                                    <i data-lucide="key" class="w-4 h-4"></i>
                                                </button>
                                                <button onclick="showRoleModal({{ $user->id }})" class="pw-btn-icon-view inline-flex items-center justify-center rounded-lg p-1.5" title="Ver roles">
                                                    <i data-lucide="shield" class="w-4 h-4"></i>
                                                </button>
                                                <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline js-user-delete" data-confirm="¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer." data-confirm-danger="1">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Role Modal -->
    <div id="roleModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="pw-modal-content bg-white rounded-lg p-8 w-full overflow-y-auto">
            <div class="text-center mb-6">
                <h3 class="text-xl font-bold text-gray-800">Cambiar Rol de Usuario</h3>
                <p class="text-gray-600">Selecciona el nuevo rol para el usuario</p>
            </div>
            
            <form id="roleForm" method="POST" action="#">
                @csrf
                <input type="hidden" id="userId" name="user_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Usuario</label>
                    <input type="text" id="userName" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" readonly>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rol Actual</label>
                    <input type="text" id="currentRole" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" readonly>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nuevo Rol</label>
                    <select id="newRole" name="role_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Selecciona un rol</option>
                        @foreach(App\Models\Role::where('active', true)->get() as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="flex space-x-3">
                    <button type="submit" class="pw-btn-primary flex-1 py-2 rounded-lg font-semibold">
                        Actualizar Rol
                    </button>
                    <button type="button" onclick="closeRoleModal()" class="flex-1 bg-gray-300 text-gray-700 py-2 rounded-lg font-semibold hover:bg-gray-400">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Toggle sidebar functionality
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('hidden');
        }

        // Toggle submenu functionality
        function toggleSubmenu(menu) {
            const submenu = document.getElementById(menu + '-submenu');
            const arrow = document.getElementById(menu + '-arrow');
            
            // Toggle current submenu sin cerrar los otros
            submenu.classList.toggle('hidden');
            if (submenu.classList.contains('hidden')) {
                arrow.style.transform = 'rotate(0deg)';
            } else {
                arrow.style.transform = 'rotate(180deg)';
            }
        }

        // Selección múltiple y eliminación masiva
        (function initBulkDelete() {
            const selectAll = document.getElementById('selectAllUsers');
            const getRowCbs = () => Array.from(document.querySelectorAll('.userRowCheckbox'));

            const headerActions = document.querySelector('header .px-6 .flex.items-center.justify-between .flex.items-center.space-x-4');
            if (headerActions && !document.getElementById('bulkDeleteBtn')) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.id = 'bulkDeleteBtn';
                btn.className = 'px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors bg-red-600 text-white hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed';
                btn.disabled = true;
                btn.innerHTML = '<i data-lucide="trash-2" class="w-5 h-5"></i><span>Eliminar seleccionados</span>';
                btn.addEventListener('click', () => {
                    const selected = getRowCbs().filter(c => c.checked).length;
                    if (!selected) return;
                    const ok = confirm(`¿Eliminar ${selected} usuario(s) seleccionados? Esta acción no se puede deshacer.`);
                    if (!ok) return;
                    document.getElementById('bulkDeleteForm')?.submit();
                });
                headerActions.prepend(btn);
                lucide.createIcons();
            }

            function sync() {
                const all = getRowCbs();
                const checked = all.filter(c => c.checked);
                const btn = document.getElementById('bulkDeleteBtn');
                if (btn) btn.disabled = checked.length === 0;
                if (selectAll) selectAll.checked = all.length > 0 && checked.length === all.length;
            }

            if (selectAll) {
                selectAll.addEventListener('change', (e) => {
                    getRowCbs().forEach(c => (c.checked = e.target.checked));
                    sync();
                });
            }

            document.addEventListener('change', (e) => {
                if (e.target && e.target.classList && e.target.classList.contains('userRowCheckbox')) {
                    sync();
                }
            });

            sync();
        })();

        // Role Modal functionality
        function showRoleModal(userId) {
            // This would typically fetch user data via AJAX
            // For now, we'll use a simple approach
            const modal = document.getElementById('roleModal');
            modal.classList.remove('hidden');
            
            // Set user data (you'd fetch this from the server)
            document.getElementById('userId').value = userId;
            document.getElementById('userName').value = 'Usuario ' + userId;
            document.getElementById('currentRole').value = 'Rol actual';
        }

        function closeRoleModal() {
            const modal = document.getElementById('roleModal');
            modal.classList.add('hidden');
        }

        function resetPassword(userId) {
            if (confirm('¿Estás seguro de restablecer la contraseña de este usuario?')) {
                // Submit form to reset password
                window.location.href = `/users/${userId}/reset-password`;
            }
        }

        // Eliminar sin refrescar (single + bulk)
        (function initAjaxDeletes() {
            function parseIntSafe(el) {
                const n = parseInt((el?.textContent || '0').trim(), 10);
                return Number.isFinite(n) ? n : 0;
            }

            function setStat(id, value) {
                const el = document.getElementById(id);
                if (!el) return;
                el.textContent = String(Math.max(0, value));
            }

            function showToast(message, type = 'success') {
                const containerId = 'toastContainer';
                let container = document.getElementById(containerId);
                if (!container) {
                    container = document.createElement('div');
                    container.id = containerId;
                    container.className = 'fixed top-4 right-4 z-[9999] space-y-2';
                    document.body.appendChild(container);
                }
                const bg = type === 'success' ? 'bg-green-600' : (type === 'warning' ? 'bg-yellow-600' : 'bg-red-600');
                const toast = document.createElement('div');
                toast.className = `${bg} text-white px-4 py-3 rounded-lg shadow-lg text-sm max-w-sm`;
                toast.textContent = message;
                container.appendChild(toast);
                setTimeout(() => toast.remove(), 3500);
            }

            async function postFormAsJson(form) {
                const fd = new FormData(form);
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: fd,
                });
                const data = await res.json().catch(() => null);
                if (!res.ok || !data) {
                    const msg = (data && (data.message || data.error)) || 'No se pudo completar la acción.';
                    throw new Error(msg);
                }
                return data;
            }

            function applyDeleteEffects(rows) {
                const total = parseIntSafe(document.getElementById('statTotalUsers'));
                const active = parseIntSafe(document.getElementById('statActiveUsers'));
                const inactive = parseIntSafe(document.getElementById('statInactiveUsers'));

                let decTotal = 0, decActive = 0, decInactive = 0;
                rows.forEach((tr) => {
                    if (!tr) return;
                    const isActive = (tr.getAttribute('data-user-active') === '1');
                    decTotal += 1;
                    if (isActive) decActive += 1;
                    else decInactive += 1;
                    tr.remove();
                });

                setStat('statTotalUsers', total - decTotal);
                setStat('statActiveUsers', active - decActive);
                setStat('statInactiveUsers', inactive - decInactive);
            }

            document.addEventListener('submit', async (e) => {
                const form = e.target;
                if (!(form instanceof HTMLFormElement)) return;

                const isSingle = form.classList.contains('js-user-delete');
                const isBulk = form.id === 'bulkDeleteForm';
                if (!isSingle && !isBulk) return;

                e.preventDefault();

                if (isSingle) {
                    const msg = form.getAttribute('data-confirm') || '¿Eliminar usuario?';
                    if (!confirm(msg)) return;
                }

                try {
                    const data = await postFormAsJson(form);

                    if (isSingle) {
                        const tr = form.closest('tr[data-user-id]');
                        applyDeleteEffects([tr]);
                    } else if (isBulk) {
                        const selected = Array.from(form.querySelectorAll('.userRowCheckbox:checked'))
                            .map(cb => cb.value);
                        const rows = selected.map(id => document.querySelector(`tr[data-user-id="${id}"]`)).filter(Boolean);
                        applyDeleteEffects(rows);

                        // Limpia selección
                        form.querySelectorAll('.userRowCheckbox').forEach(cb => (cb.checked = false));
                        const selAll = document.getElementById('selectAllUsers');
                        if (selAll) selAll.checked = false;
                        const btn = document.getElementById('bulkDeleteBtn');
                        if (btn) btn.disabled = true;
                    }

                    showToast(data.message || 'Eliminado correctamente.', 'success');
                } catch (err) {
                    showToast(err?.message || 'Error eliminando usuario(s).', 'error');
                }
            });
        })();
    </script>
</body>
</html>
