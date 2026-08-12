<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gestión Completa de Usuarios - SAMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        .sidebar-item { transition: background-color 0.2s ease; }
        .sidebar-item:hover { background-color: rgba(99, 102, 241, 0.1); }
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Background Pattern -->
    <div class="fixed inset-0 -z-10">
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 via-white to-purple-50"></div>
    </div>

    <!-- Notifications Container -->
    <div id="notificationsContainer"></div>

    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside id="sidebar" class="w-64 bg-white shadow-lg relative">
            <div class="p-6">
                <div class="flex items-center space-x-3 mb-8">
                    <div class="w-10 h-10 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center">
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
                    <!-- Gestión Principal -->
                    <div class="relative">
                        <button onclick="toggleSubmenu('gestion')" class="sidebar-item w-full flex items-center justify-between px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <i data-lucide="menu" class="w-5 h-5"></i>
                                <span class="font-semibold">Gestión Principal</span>
                            </div>
                            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="gestion-arrow"></i>
                        </button>
                        <div id="gestion-submenu" class="hidden pl-4 pr-4 py-2 space-y-1">
                            <a href="{{ route('users.complete') }}" class="sidebar-item w-full flex items-center px-4 py-3 text-indigo-600 hover:bg-indigo-50 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="users" class="w-5 h-5"></i>
                                    <span class="font-medium">Usuarios</span>
                                </div>
                            </a>
                            <a href="{{ route('roles.complete') }}" class="sidebar-item w-full flex items-center px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="shield" class="w-5 h-5"></i>
                                    <span class="font-medium">Roles</span>
                                </div>
                            </a>
                            <a href="{{ route('cargos.complete') }}" class="sidebar-item w-full flex items-center px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="briefcase" class="w-5 h-5"></i>
                                    <span class="font-medium">Cargos</span>
                                </div>
                            </a>
                            <a href="{{ route('grupos.complete') }}" class="sidebar-item w-full flex items-center px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="layers" class="w-5 h-5"></i>
                                    <span class="font-medium">Grupos</span>
                                </div>
                            </a>
                            <a href="{{ route('fabricantes.complete') }}" class="sidebar-item w-full flex items-center px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="factory" class="w-5 h-5"></i>
                                    <span class="font-medium">Fabricantes</span>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Gestión de Equipos -->
                    <div class="relative">
                        <button onclick="toggleSubmenu('equipos')" class="sidebar-item w-full flex items-center justify-between px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <i data-lucide="monitor" class="w-5 h-5"></i>
                                <span class="font-semibold">Gestión de Equipos</span>
                            </div>
                            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="equipos-arrow"></i>
                        </button>
                        <div id="equipos-submenu" class="hidden pl-4 pr-4 py-2 space-y-1">
                            <a href="{{ route('equipos.complete') }}" class="sidebar-item w-full flex items-center px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="server" class="w-5 h-5"></i>
                                    <span class="font-medium">Todos los Equipos</span>
                                </div>
                            </a>
                            <a href="{{ route('equipos.tipos') }}" class="sidebar-item w-full flex items-center px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="layers" class="w-5 h-5"></i>
                                    <span class="font-medium">Tipos de Equipos</span>
                                </div>
                            </a>
                            <a href="{{ route('equipos.material-didactico') }}" class="sidebar-item w-full flex items-center px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="book-open" class="w-5 h-5"></i>
                                    <span class="font-medium">Material Didáctico</span>
                                </div>
                            </a>
                            <a href="{{ route('equipos.equipos-baja') }}" class="sidebar-item w-full flex items-center px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="archive" class="w-5 h-5"></i>
                                    <span class="font-medium">Equipos de Baja</span>
                                </div>
                            </a>
                            <a href="{{ route('equipos.auditoria') }}" class="sidebar-item w-full flex items-center px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="clipboard-check" class="w-5 h-5"></i>
                                    <span class="font-medium">Auditoría de Equipos</span>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Gestión de Empresa -->
                    <div class="relative">
                        <button onclick="toggleSubmenu('empresa')" class="sidebar-item w-full flex items-center justify-between px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <i data-lucide="building" class="w-5 h-5"></i>
                                <span class="font-semibold">Gestión de Empresa</span>
                            </div>
                            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="empresa-arrow"></i>
                        </button>
                        <div id="empresa-submenu" class="hidden pl-4 pr-4 py-2 space-y-1">
                            <a href="#" onclick="showMessage('Personalización en desarrollo')" class="sidebar-item w-full flex items-center px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="settings" class="w-5 h-5"></i>
                                    <span class="font-medium">Personalización</span>
                                </div>
                            </a>
                            <a href="#" onclick="showMessage('Empresa en desarrollo')" class="sidebar-item w-full flex items-center px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="building" class="w-5 h-5"></i>
                                    <span class="font-medium">Empresa</span>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Configuración -->
                    <div class="relative">
                        <button onclick="toggleSubmenu('config')" class="sidebar-item w-full flex items-center justify-between px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <i data-lucide="settings" class="w-5 h-5"></i>
                                <span class="font-semibold">Configuración</span>
                            </div>
                            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" id="config-arrow"></i>
                        </button>
                        <div id="config-submenu" class="hidden pl-4 pr-4 py-2 space-y-1">
                            <a href="#" onclick="showMessage('Formatos en desarrollo')" class="sidebar-item w-full flex items-center px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="file-text" class="w-5 h-5"></i>
                                    <span class="font-medium">Formatos</span>
                                </div>
                            </a>
                            <a href="#" onclick="showMessage('Hoja de Vida en desarrollo')" class="sidebar-item w-full flex items-center px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="file-text" class="w-5 h-5"></i>
                                    <span class="font-medium">Hoja de Vida</span>
                                </div>
                            </a>
                            <a href="#" onclick="showMessage('Inspección en desarrollo')" class="sidebar-item w-full flex items-center px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="file-text" class="w-5 h-5"></i>
                                    <span class="font-medium">Inspección</span>
                                </div>
                            </a>
                            <a href="#" onclick="showMessage('Exportación en desarrollo')" class="sidebar-item w-full flex items-center px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <i data-lucide="download" class="w-5 h-5"></i>
                                    <span class="font-medium">Exportar</span>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Asignar -->
                    <div class="relative">
                        <button onclick="showMessage('Asignar en desarrollo')" class="sidebar-item w-full flex items-center px-4 py-3 text-gray-700 hover:text-indigo-600 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <i data-lucide="user-check" class="w-5 h-5"></i>
                                <span class="font-semibold">Asignar</span>
                            </div>
                        </button>
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
            <header class="bg-white shadow-sm border-b border-gray-200">
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
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-6">
                <div>
                    <!-- Tabs -->
                    <div class="mb-6 border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8">
                            <button onclick="showTab('todos')" data-tab="todos" class="py-4 px-1 border-b-2 border-indigo-500 font-medium text-sm text-indigo-600">
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
                            <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg">
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
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            } catch (error) {
                console.error('Error initializing icons:', error);
            }
        });

        // Simple notification system
        function showMessage(message) {
            alert(message);
        }

        // Toggle submenu
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
                            alert('Usuario actualizado exitosamente');
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
                            alert(data.message || 'Contraseña restablecida exitosamente');
                            location.reload();
                        } else {
                            alert('Error al restablecer contraseña');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al restablecer contraseña');
                    });
                }
            } catch (error) {
                console.error('Error resetting password:', error);
                alert('Error al restablecer contraseña');
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
                            alert('Usuario eliminado exitosamente');
                            location.reload();
                        } else {
                            alert('Error al eliminar usuario');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al eliminar usuario');
                    });
                }
            } catch (error) {
                console.error('Error deleting user:', error);
                alert('Error al eliminar usuario');
            }
        }

        function showRoleModal(userId) {
            try {
                alert('Función de rol en desarrollo para usuario: ' + userId);
            } catch (error) {
                console.error('Error showing role modal:', error);
            }
        }

        // Initialize with gestion submenu open
        document.addEventListener('DOMContentLoaded', function() {
            try {
                const gestionSubmenu = document.getElementById('gestion-submenu');
                const gestionArrow = document.getElementById('gestion-arrow');
                if (gestionSubmenu && gestionArrow) {
                    gestionSubmenu.classList.remove('hidden');
                    gestionArrow.style.transform = 'rotate(180deg)';
                }
            } catch (error) {
                console.error('Error initializing submenu:', error);
            }
        });
    </script>
</body>
</html>
