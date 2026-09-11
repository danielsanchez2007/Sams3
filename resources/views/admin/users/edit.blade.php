<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Editar Usuario - SAMS</title>
    <x-sams-assets />

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
                            <h2 class="text-2xl font-bold text-gray-800">Editar Usuario</h2>
                            <p class="text-gray-600">Modificar información del usuario</p>
                        </div>
                        
                        <div class="flex items-center space-x-4">
                            <a href="{{ route('users.complete') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                                <i data-lucide="arrow-left" class="w-4 h-4 inline mr-2"></i>
                                Volver
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
                    <div class="pw-card bg-white rounded-xl shadow-lg p-8">
                        <form method="POST" action="{{ route('users.update', $user) }}" enctype="multipart/form-data" class="space-y-6">
                            @csrf
                            @method('PUT')
                            
                            <!-- Información Personal -->
                            <div class="border-b pb-6">
                                <h4 class="text-lg font-medium text-gray-900 mb-4">Información Personal</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                                        <input type="text" name="name" value="{{ $user->name }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Apellidos *</label>
                                        <input type="text" name="last_name" value="{{ $user->last_name ?? '' }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Documento *</label>
                                        <select name="document_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="">Selecciona...</option>
                                            <option value="CC" {{ $user->document_type == 'CC' ? 'selected' : '' }}>Cédula de Ciudadanía</option>
                                            <option value="CE" {{ $user->document_type == 'CE' ? 'selected' : '' }}>Cédula de Extranjería</option>
                                            <option value="TI" {{ $user->document_type == 'TI' ? 'selected' : '' }}>Tarjeta de Identidad</option>
                                            <option value="PP" {{ $user->document_type == 'PP' ? 'selected' : '' }}>Pasaporte</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Número de Documento *</label>
                                        <input type="text" name="document_number" value="{{ $user->document_number ?? '' }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Nacimiento</label>
                                        <input type="date" name="birth_date" value="{{ $user->birth_date ? $user->birth_date->format('Y-m-d') : '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Edad (calculada automáticamente)</label>
                                        <input type="text" id="ageDisplay" readonly class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
                                    </div>
                                </div>
                            </div>

                            <!-- Contacto -->
                            <div class="border-b pb-6">
                                <h4 class="text-lg font-medium text-gray-900 mb-4">Información de Contacto</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Correo Personal *</label>
                                        <input type="email" name="email" value="{{ $user->email }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono Personal</label>
                                        <input type="tel" name="phone" value="{{ $user->phone ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="hasCorporateEmail" name="has_corporate_email" {{ $user->has_corporate_email ? 'checked' : '' }} class="mr-2">
                                        <label for="hasCorporateEmail" class="text-sm font-medium text-gray-700">¿Tiene correo corporativo?</label>
                                    </div>
                                    <div id="corporateEmailField" class="{{ !$user->has_corporate_email ? 'hidden' : '' }}">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Correo Corporativo</label>
                                        <input type="email" name="corporate_email" value="{{ $user->corporate_email ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="hasCorporatePhone" name="has_corporate_phone" {{ $user->has_corporate_phone ? 'checked' : '' }} class="mr-2">
                                        <label for="hasCorporatePhone" class="text-sm font-medium text-gray-700">¿Tiene teléfono corporativo?</label>
                                    </div>
                                    <div id="corporatePhoneField" class="{{ !$user->has_corporate_phone ? 'hidden' : '' }}">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono Corporativo</label>
                                        <input type="tel" name="corporate_phone" value="{{ $user->corporate_phone ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>
                            </div>

                            <!-- Archivos -->
                            <div class="border-b pb-6">
                                <h4 class="text-lg font-medium text-gray-900 mb-4">Archivos (Opcional)</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Foto de Perfil</label>
                                        <input type="file" name="photo" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        @if($user->photo)
                                        <p class="text-xs text-gray-500 mt-1">Foto actual: {{ $user->photo }}</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Firma Digital</label>
                                        <input type="file" name="signature" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        @if($user->signature)
                                        <p class="text-xs text-gray-500 mt-1">Firma actual: {{ $user->signature }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Asignaciones -->
                            <div class="border-b pb-6">
                                <h4 class="text-lg font-medium text-gray-900 mb-4">Asignaciones</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Rol</label>
                                        <select name="role_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="">Selecciona un rol</option>
                                            @foreach($roles as $role)
                                            <option value="{{ $role->id }}" {{ $user->role_id == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Cargo</label>
                                        <select name="cargo_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="">Selecciona un cargo</option>
                                            @foreach($cargos as $cargo)
                                            <option value="{{ $cargo->id }}" {{ $user->cargo_id == $cargo->id ? 'selected' : '' }}>{{ $cargo->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Grupo</label>
                                        <select name="grupo_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="">Selecciona un grupo</option>
                                            @foreach($grupos as $grupo)
                                            <option value="{{ $grupo->id }}" {{ $user->grupo_id == $grupo->id ? 'selected' : '' }}>{{ $grupo->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Estado -->
                            <div class="pb-6">
                                <h4 class="text-lg font-medium text-gray-900 mb-4">Estado</h4>
                                <div class="flex items-center">
                                    <input type="checkbox" name="active" id="active" value="1" {{ $user->active ? 'checked' : '' }} class="mr-2">
                                    <label for="active" class="text-sm font-medium text-gray-700">Usuario activo</label>
                                </div>
                            </div>

                            <!-- Contraseña -->
                            <div class="pb-6">
                                <h4 class="text-lg font-medium text-gray-900 mb-4">Contraseña (dejar en blanco para mantener la actual)</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Nueva Contraseña</label>
                                        <input type="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" autocomplete="new-password" minlength="10" maxlength="72" data-pw-meter="optional">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirmar Contraseña</label>
                                        <input type="password" name="password_confirmation" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>
                            </div>

                            <!-- Botones -->
                            <div class="flex justify-end space-x-4">
                                <a href="{{ route('users.complete') }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                                    Cancelar
                                </a>
                                <button type="submit" class="pw-btn-primary px-6 py-2 rounded-lg">
                                    Actualizar Usuario
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
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
            
            submenu.classList.toggle('hidden');
            if (submenu.classList.contains('hidden')) {
                arrow.style.transform = 'rotate(0deg)';
            } else {
                arrow.style.transform = 'rotate(180deg)';
            }
        }

        // Corporate email toggle
        document.getElementById('hasCorporateEmail').addEventListener('change', function() {
            const field = document.getElementById('corporateEmailField');
            field.classList.toggle('hidden', !this.checked);
        });

        // Corporate phone toggle
        document.getElementById('hasCorporatePhone').addEventListener('change', function() {
            const field = document.getElementById('corporatePhoneField');
            field.classList.toggle('hidden', !this.checked);
        });

        // Calculate age
        const birthDateInput = document.querySelector('input[name="birth_date"]');
        if (birthDateInput) {
            birthDateInput.addEventListener('change', function() {
                if (this.value) {
                    const birthDate = new Date(this.value);
                    const today = new Date();
                    let age = today.getFullYear() - birthDate.getFullYear();
                    const monthDiff = today.getMonth() - birthDate.getMonth();
                    
                    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                        age--;
                    }
                    
                    document.getElementById('ageDisplay').value = age + ' años';
                } else {
                    document.getElementById('ageDisplay').value = '';
                }
            });

            // Calculate initial age if birth_date is set
            if (birthDateInput.value) {
                birthDateInput.dispatchEvent(new Event('change'));
            }
        }
    </script>
</body>
</html>
