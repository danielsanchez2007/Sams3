<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gestión Completa de Usuarios - SAMS</title>
    <x-sams-assets />

    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideIn {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }
        .animate-fade-in { animation: fadeIn 0.5s ease-out; }
        .animate-slide-in { animation: slideIn 0.3s ease-out; }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar-item { transition: all 0.3s ease; }
        .sidebar-item:hover { transform: translateX(5px); background: rgba(99, 102, 241, 0.1); }
        .tab-active { border-bottom: 2px solid #4f46e5; color: #4f46e5; }
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
            animation: slideInRight 0.3s ease-out;
        }
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Background Pattern -->
    <div class="fixed inset-0 -z-10">
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 via-white to-purple-50"></div>
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%239C92AC" fill-opacity="0.03"%3E%3Cpath d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
    </div>

    <!-- Notifications Container -->
    <div id="notificationsContainer"></div>

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
                        <i data-lucide="users" class="w-6 h-6 text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">SAMS</h1>
                        <p class="text-xs text-gray-600">Gestión de Usuarios</p>
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
                            <!-- Usuarios - Direct Link -->
                            <a href="{{ route('users.complete') }}" class="sidebar-item w-full flex items-center px-4 py-3 text-indigo-600 hover:bg-indigo-50 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="users" class="w-5 h-5"></i>
                                    <span class="font-medium">Usuarios</span>
                                </div>
                            </a>

                            <!-- Roles - Direct Link -->
                            <a href="{{ route('roles.complete') }}" class="sidebar-item w-full flex items-center px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="shield" class="w-5 h-5"></i>
                                    <span class="font-medium">Roles</span>
                                </div>
                            </a>

                            <!-- Cargos - Direct Link -->
                            <a href="{{ route('cargos.complete') }}" class="sidebar-item w-full flex items-center px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="briefcase" class="w-5 h-5"></i>
                                    <span class="font-medium">Cargos</span>
                                </div>
                            </a>

                            <!-- Grupos - Direct Link -->
                            <a href="{{ route('grupos.complete') }}" class="sidebar-item w-full flex items-center px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="layers" class="w-5 h-5"></i>
                                    <span class="font-medium">Grupos</span>
                                </div>
                            </a>

                            <!-- Fabricantes - Direct Link -->
                            <a href="{{ route('fabricantes.complete') }}" class="sidebar-item w-full flex items-center px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="factory" class="w-5 h-5"></i>
                                    <span class="font-medium">Fabricantes</span>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Gestión de Equipos - Desplegable -->
                    <div class="relative">
                        <button onclick="toggleSubmenu('equipos')" class="sidebar-item w-full flex items-center justify-between px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <i data-lucide="monitor" class="w-5 h-5"></i>
                                <span class="font-semibold">Gestión de Equipos</span>
                            </div>
                            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="equipos-arrow"></i>
                        </button>
                        <div id="equipos-submenu" class="hidden pl-4 pr-4 py-2 space-y-1">
                            <!-- Equipos General -->
                            <a href="{{ route('equipos.complete') }}" class="sidebar-item w-full flex items-center px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="server" class="w-5 h-5"></i>
                                    <span class="font-medium">Todos los Equipos</span>
                                </div>
                            </a>

                            <!-- Tipos de Equipos -->
                            <a href="{{ route('equipos.tipos') }}" class="sidebar-item w-full flex items-center px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="layers" class="w-5 h-5"></i>
                                    <span class="font-medium">Tipos de Equipos</span>
                                </div>
                            </a>

                            <!-- Material Didáctico -->
                            <a href="{{ route('equipos.material-didactico') }}" class="sidebar-item w-full flex items-center px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="book-open" class="w-5 h-5"></i>
                                    <span class="font-medium">Material Didáctico</span>
                                </div>
                            </a>

                            <!-- Equipos de Baja -->
                            <a href="{{ route('equipos.equipos-baja') }}" class="sidebar-item w-full flex items-center px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="archive" class="w-5 h-5"></i>
                                    <span class="font-medium">Equipos de Baja</span>
                                </div>
                            </a>

                            <!-- Auditoría de Equipos -->
                            <a href="{{ route('equipos.auditoria') }}" class="sidebar-item w-full flex items-center px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="clipboard-check" class="w-5 h-5"></i>
                                    <span class="font-medium">Auditoría de Equipos</span>
                                </div>
                            </a>
                        </div>
                    </div>

                    <div class="border-t pt-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Sistema</p>
                        
                        <a href="{{ route('admin.dashboard') }}" class="sidebar-item flex items-center space-x-3 px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                            <i data-lucide="home" class="w-5 h-5"></i>
                            <span class="font-medium">Dashboard</span>
                        </a>
                        
                        <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="sidebar-item flex items-center space-x-3 px-4 py-3 text-red-600 hover:text-red-700 rounded-lg w-full text-left">
                                <i data-lucide="log-out" class="w-5 h-5"></i>
                                <span class="font-medium">Cerrar Sesión</span>
                            </button>
                        </form>
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
                            <h2 class="text-2xl font-bold text-gray-800">Gestión Completa de Usuarios</h2>
                            <p class="text-gray-600">Administración completa de usuarios y permisos</p>
                        </div>
                        
                        <div class="flex items-center space-x-4">
                            <button onclick="showCreateModal()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>
                                Nuevo Usuario
                            </button>
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
                    <!-- Tabs -->
                    <div class="mb-6 border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8">
                            <button onclick="showTab('todos')" data-tab="todos" class="tab-active py-4 px-1 border-b-2 font-medium text-sm">
                                Todos los Usuarios
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
                            @foreach($users as $user)
                            <div class="pw-card bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-center space-x-3">
                                        <img src="https://picsum.photos/seed/{{ $user->id }}/40/40.jpg" alt="Avatar" class="w-12 h-12 rounded-full border-2 border-indigo-500">
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-800">{{ $user->name }} {{ $user->last_name ?? '' }}</h3>
                                            <p class="text-sm text-gray-600">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="px-2 py-1 {{ $user->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} text-xs rounded-full">
                                            {{ $user->active ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div class="flex items-center space-x-2 text-sm text-gray-600">
                                        <i data-lucide="credit-card" class="w-4 h-4"></i>
                                        <span>{{ $user->document_type }}: {{ $user->document_number }}</span>
                                    </div>
                                    @if($user->phone)
                                    <div class="flex items-center space-x-2 text-sm text-gray-600">
                                        <i data-lucide="phone" class="w-4 h-4"></i>
                                        <span>{{ $user->phone }}</span>
                                    </div>
                                    @endif
                                    @if($user->role)
                                    <div class="flex items-center space-x-2 text-sm text-gray-600">
                                        <i data-lucide="shield" class="w-4 h-4"></i>
                                        <span>{{ $user->role->name }}</span>
                                    </div>
                                    @endif
                                    @if($user->cargo)
                                    <div class="flex items-center space-x-2 text-sm text-gray-600">
                                        <i data-lucide="briefcase" class="w-4 h-4"></i>
                                        <span>{{ $user->cargo->name }}</span>
                                    </div>
                                    @endif
                                    @if($user->grupo)
                                    <div class="flex items-center space-x-2 text-sm text-gray-600">
                                        <i data-lucide="layers" class="w-4 h-4"></i>
                                        <span>{{ $user->grupo->name }}</span>
                                    </div>
                                    @endif
                                </div>

                                <div class="flex justify-between items-center pt-4 border-t border-gray-100">
                                    <div class="flex space-x-2">
                                        <button onclick="editUser({{ $user->id }})" class="pw-btn-icon-edit inline-flex items-center justify-center rounded-lg" title="Editar">
                                            <i data-lucide="edit-2"></i>
                                        </button>
                                        <button onclick="toggleStatus({{ $user->id }})" class="inline-flex items-center justify-center rounded-lg {{ $user->active ? 'pw-btn-icon-toggle-active' : 'pw-btn-icon-toggle-inactive' }}" title="{{ $user->active ? 'Desactivar' : 'Activar' }}">
                                            <i data-lucide="power"></i>
                                        </button>
                                        <button onclick="resetPassword({{ $user->id }})" class="p-2 text-green-600 hover:bg-green-50 rounded-lg">
                                            <i data-lucide="key" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button onclick="showRoleModal({{ $user->id }})" class="pw-btn-icon-view inline-flex items-center justify-center rounded-lg" title="Ver roles">
                                            <i data-lucide="shield" class="w-4 h-4"></i>
                                        </button>
                                        <button onclick="deleteUser({{ $user->id }})" class="pw-btn-icon-delete inline-flex items-center justify-center rounded-lg" title="Eliminar">
                                            <i data-lucide="trash-2"></i>
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
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Usuarios Activos</h3>
                            <p class="text-gray-600">Mostrando solo usuarios activos</p>
                        </div>
                    </div>

                    <div id="inactivos-tab" class="tab-content hidden">
                        <div class="text-center py-12">
                            <i data-lucide="x-circle" class="w-16 h-16 text-red-500 mx-auto mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Usuarios Inactivos</h3>
                            <p class="text-gray-600">Mostrando solo usuarios inactivos</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Create Modal -->
    <div id="createModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center pb-4">
                <h3 class="text-lg font-medium text-gray-900">Nuevo Usuario</h3>
                <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form action="{{ route('users.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Apellido *</label>
                    <input type="text" name="last_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                    <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo Documento *</label>
                    <select name="document_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Selecciona...</option>
                        <option value="CC">Cédula de Ciudadanía</option>
                        <option value="CE">Cédula de Extranjería</option>
                        <option value="PASS">Pasaporte</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Número Documento *</label>
                    <input type="text" name="document_number" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeCreateModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        Crear Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        document.addEventListener('DOMContentLoaded', function() {
            try {
                lucide.createIcons();
                console.log('Icons initialized successfully');
            } catch (error) {
                console.error('Error initializing icons:', error);
            }
        });

        // Safe function to show notifications
        function showNotification(message, type = 'success') {
            try {
                const container = document.getElementById('notificationsContainer');
                const notification = document.createElement('div');
                notification.className = `notification ${type === 'success' ? 'bg-green-50 border-green-500' : type === 'error' ? 'bg-red-50 border-red-500' : 'bg-yellow-50 border-yellow-500'} border-l-4 p-4 rounded-lg shadow-lg`;
                notification.innerHTML = `
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 ${type === 'success' ? 'bg-green-100' : type === 'error' ? 'bg-red-100' : 'bg-yellow-100'} rounded-full flex items-center justify-center">
                                <i data-lucide="${type === 'success' ? 'check-circle' : type === 'error' ? 'x-circle' : 'alert-triangle'}" class="w-5 h-5 ${type === 'success' ? 'text-green-600' : type === 'error' ? 'text-red-600' : 'text-yellow-600'}"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <p class="pw-notify-msg ${type === 'success' ? 'text-green-800' : type === 'error' ? 'text-red-800' : 'text-yellow-800'} font-medium"></p>
                        </div>
                        <button onclick="this.parentElement.parentElement.remove()" class="${type === 'success' ? 'text-green-600' : type === 'error' ? 'text-red-600' : 'text-yellow-600'} hover:${type === 'success' ? 'text-green-800' : type === 'error' ? 'text-red-800' : 'text-yellow-800'}">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                `;
                const msgEl = notification.querySelector('.pw-notify-msg');
                if (msgEl) msgEl.textContent = message || '';
                container.appendChild(notification);
                
                // Auto-remove after 5 seconds
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, 5000);
                
                // Re-initialize icons for the new notification
                setTimeout(() => {
                    try {
                        lucide.createIcons();
                    } catch (error) {
                        console.error('Error re-initializing icons:', error);
                    }
                }, 100);
                
            } catch (error) {
                console.error('Error showing notification:', error);
                alert(message); // Fallback to alert
            }
        }

        // Toggle sidebar functionality
        function toggleSidebar() {
            try {
                const sidebar = document.getElementById('sidebar');
                if (sidebar) {
                    sidebar.classList.toggle('hidden');
                }
            } catch (error) {
                console.error('Error toggling sidebar:', error);
            }
        }

        // Toggle submenu functionality
        function toggleSubmenu(menu) {
            try {
                const submenu = document.getElementById(menu + '-submenu');
                const arrow = document.getElementById(menu + '-arrow');
                
                if (submenu && arrow) {
                    submenu.classList.toggle('hidden');
                    if (submenu.classList.contains('hidden')) {
                        arrow.style.transform = 'rotate(0deg)';
                    } else {
                        arrow.style.transform = 'rotate(180deg)';
                    }
                }
            } catch (error) {
                console.error('Error toggling submenu:', error);
            }
        }

        // Tab functionality
        function showTab(tabName) {
            try {
                // Hide all tabs
                document.querySelectorAll('.tab-content').forEach(tab => {
                    tab.classList.add('hidden');
                });
                
                // Remove active class from all buttons
                document.querySelectorAll('[data-tab]').forEach(button => {
                    button.classList.remove('tab-active');
                    button.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                });
                
                // Show selected tab
                const selectedTab = document.getElementById(tabName + '-tab');
                if (selectedTab) {
                    selectedTab.classList.remove('hidden');
                }
                
                // Add active class to selected button
                const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
                if (activeButton) {
                    activeButton.classList.add('tab-active');
                    activeButton.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
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
        function editUser(userId) {
            try {
                window.location.href = `/users/${userId}/edit`;
            } catch (error) {
                console.error('Error editing user:', error);
            }
        }

        function toggleStatus(userId) {
            try {
                if (confirm('¿Estás seguro de cambiar el estado de este usuario?')) {
                    fetch(`/users/${userId}/toggle-status`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Usuario actualizado exitosamente', 'success');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            showNotification('Error al cambiar estado', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error al cambiar estado', 'error');
                    });
                }
            } catch (error) {
                console.error('Error toggling status:', error);
                showNotification('Error al cambiar estado', 'error');
            }
        }

        function resetPassword(userId) {
            try {
                if (confirm('¿Estás seguro de restablecer la contraseña de este usuario?')) {
                    fetch(`/users/${userId}/reset-password`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message || 'Contraseña restablecida exitosamente', 'success');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            showNotification('Error al restablecer contraseña', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error al restablecer contraseña', 'error');
                    });
                }
            } catch (error) {
                console.error('Error resetting password:', error);
                showNotification('Error al restablecer contraseña', 'error');
            }
        }

        function deleteUser(userId) {
            try {
                if (confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.')) {
                    fetch(`/users/${userId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Usuario eliminado exitosamente', 'success');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            showNotification('Error al eliminar usuario', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error al eliminar usuario', 'error');
                    });
                }
            } catch (error) {
                console.error('Error deleting user:', error);
                showNotification('Error al eliminar usuario', 'error');
            }
        }

        function showRoleModal(userId) {
            try {
                alert('Función de rol en desarrollo para usuario: ' + userId);
            } catch (error) {
                console.error('Error showing role modal:', error);
            }
        }

        // Check for session messages and show as notifications
        document.addEventListener('DOMContentLoaded', function() {
            try {
                // Check if there are any session messages
                @if(session()->has('success'))
                showNotification('{{ session('success') }}', 'success');
                @endif
                
                @if(session()->has('error'))
                showNotification('{{ session('error') }}', 'error');
                @endif
                
                @if(session()->has('warning'))
                showNotification('{{ session('warning') }}', 'warning');
                @endif
            } catch (error) {
                console.error('Error processing session messages:', error);
            }
        });

        // Global error handler
        window.addEventListener('error', function(e) {
            console.error('JavaScript error:', e.error);
        });

        // Prevent form resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>
