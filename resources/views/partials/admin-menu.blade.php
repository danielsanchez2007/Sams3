<!-- Sidebar -->
@php
    $user = Auth::user();
    $rolePermissions = $user->role?->permissions ?? null;
    $officeModeByGlobalAdmin = \App\Services\VistaOficina::modoOficinaSesionActivo()
        && $user
        && !$user->empresa_id
        && strtolower(trim($user->role?->name ?? '')) === 'administrador';
    if ($officeModeByGlobalAdmin) {
        $adminOficinaPerms = \Illuminate\Support\Facades\Cache::remember('sams_role_perms_adminoficina', 3600, function () {
            return \App\Models\Role::query()
                ->whereRaw('LOWER(name) = ?', ['adminoficina'])
                ->value('permissions');
        });
        $rolePermissions = is_array($adminOficinaPerms) && !empty($adminOficinaPerms)
            ? $adminOficinaPerms
            : ['asignar'];
    }
    $effectivePermissions = ($user && $user->empresa_id) ? null : $rolePermissions;
    $canToggleOffice = $user && !$user->empresa_id && (strtolower(trim($user->role?->name ?? '')) === 'administrador');
    $isPreventionWorldAdmin = $canToggleOffice && !$officeModeByGlobalAdmin;
    $hasPerm = function (?array $perms, string $key): bool {
        if (empty($perms) || !is_array($perms)) {
            return true; // Sin permisos definidos => acceso completo
        }
        return in_array($key, $perms, true);
    };
    $empresaActiva = \App\Services\EmpresaContext::empresaActiva();
    $empresaActivaEsPreventionWorld = \App\Services\EmpresaContext::esPreventionWorld($empresaActiva);
    $empresaModulosRaw = $empresaActiva?->modulos ?? null; // puede ser lista simple o mapa modulo=>nivel
    $empresaModuloNivel = function (string $key) use ($empresaModulosRaw, $user, $isPreventionWorldAdmin): string {
        if ($isPreventionWorldAdmin) {
            return 'edit';
        }
        // Por defecto: si no hay configuración, todo es "edit"
        if ($empresaModulosRaw === null) {
            return ($user && $user->empresa_id) ? 'none' : 'edit';
        }
        if (!is_array($empresaModulosRaw)) {
            return ($user && $user->empresa_id) ? 'none' : 'edit';
        }
        // Compatibilidad: lista simple => módulos con acceso completo
        if (array_is_list($empresaModulosRaw)) {
            return in_array($key, $empresaModulosRaw, true) ? 'edit' : 'none';
        }
        $val = $empresaModulosRaw[$key] ?? null;
        // Compatibilidad: si no está el key pero es uno de Gestión Principal y existe gestion_principal, usarlo
        if ($val === null && in_array($key, ['users', 'roles', 'cargos', 'grupos', 'fabricantes'], true)) {
            $val = $empresaModulosRaw['gestion_principal'] ?? null;
        }
        if (in_array($val, ['none', 'view', 'edit'], true)) {
            return $val;
        }
        return $val ? 'edit' : 'none';
    };
    $vistaOficina = \App\Services\VistaOficina::mostrarMenuOficina($user);
    $modoOficinaSesion = \App\Services\VistaOficina::modoOficinaSesionActivo();
    $empresaHasModulo = function (string $key) use ($empresaModuloNivel, $isPreventionWorldAdmin): bool {
        if ($isPreventionWorldAdmin) return true;
        return $empresaModuloNivel($key) !== 'none';
    };
    $empresaCanEditModulo = function (string $key) use ($empresaModuloNivel, $isPreventionWorldAdmin): bool {
        if ($isPreventionWorldAdmin) return true;
        return $empresaModuloNivel($key) === 'edit';
    };
    $isGlobalAdmin = $user && !$user->empresa_id;
    $isGlobalOutsideEmpresa = $isGlobalAdmin && !$empresaActiva;
    $empresaIdParaSugerencias = \App\Services\EmpresaContext::empresaId() ?: $user?->empresa_id;
    $sugerenciasEstado = null;
    if ($empresaIdParaSugerencias) {
        $ultimoAviso = \App\Models\AvisoEmpresa::where('empresa_id', $empresaIdParaSugerencias)->orderByDesc('created_at')->first();
        $sugerenciasEstado = $ultimoAviso?->tipo ?? 'ok';
    }
@endphp

<aside id="sidebar" class="w-80 md:w-80 pw-sidebar fixed md:relative inset-y-0 left-0 flex flex-col transform -translate-x-full md:translate-x-0 transition-transform md:transition-all duration-200 md:flex-shrink-0" style="z-index: 50;">
    <div class="p-4 flex-shrink-0">
    <div class="flex items-center justify-end md:hidden">
            <button type="button" id="sidebarClose" class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-gray-200 bg-white hover:bg-gray-50" aria-label="Cerrar menú">
                <i data-lucide="x" class="w-5 h-5 text-gray-700"></i>
            </button>
        </div>
        <div class="sidebar-user-card">
            <div class="flex items-center gap-3">
                @if(Auth::user()->photo_url)
                    <img src="{{ Auth::user()->photo_url }}" alt="" class="sidebar-user-avatar">
                @else
                    <div class="sidebar-user-avatar sidebar-user-avatar--initials" aria-hidden="true">
                        {{ strtoupper(mb_substr(trim((string) Auth::user()->name), 0, 1)) }}
                    </div>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="sidebar-user-name truncate">{{ Auth::user()->name }}</p>
                    <p class="sidebar-user-role truncate">{{ Auth::user()->role?->name ?? 'Usuario' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Menu con Scroll -->
    <nav class="flex-1 overflow-y-auto px-4 pb-6 space-y-3">
        @if(!$isGlobalOutsideEmpresa)
        <!-- Sugerencias (oculto vista oficina) -->
        @if(!$vistaOficina)
        <div class="relative group">
            <div class="absolute -left-2 top-0 bottom-0 w-1 rounded-full opacity-0 group-hover:opacity-100 transition-opacity {{ $sugerenciasEstado === 'observacion' ? 'bg-red-500' : 'bg-green-500' }}" style="{{ $sugerenciasEstado ? 'opacity: 0.6' : '' }}"></div>
            <a href="{{ route('sugerencias.index') }}" class="sidebar-item flex items-center space-x-4 px-4 py-4 {{ request()->routeIs('sugerencias.*') ? 'sidebar-item--active' : '' }} hover:bg-teal-50 hover:text-teal-700 rounded-xl transition-all duration-200 group">
                <div class="menu-icon-box w-10 h-10 rounded-lg flex items-center justify-center transition-colors relative" data-icon="message-square">
                    <i data-lucide="message-square" class="w-5 h-5" data-icon="message-square"></i>
                    @if($sugerenciasEstado)
                    <span class="absolute -top-0.5 -right-0.5 w-3 h-3 rounded-full {{ $sugerenciasEstado === 'observacion' ? 'bg-red-500' : 'bg-green-500' }}" title="{{ $sugerenciasEstado === 'observacion' ? 'Tienes observaciones' : 'Todo correcto' }}"></span>
                    @endif
                </div>
                <div class="flex-1">
                    <span class="font-semibold text-lg">Sugerencias</span>
                    <p class="text-xs ">Avisos del administrador</p>
                </div>
            </a>
        </div>
        @endif

        <!-- Dashboard -->
        <div class="relative group">
            <div class="absolute -left-2 top-0 bottom-0 w-1 bg-blue-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <a href="{{ route('admin.dashboard') }}" class="sidebar-item flex items-center space-x-4 px-4 py-4 {{ request()->routeIs('admin.dashboard') ? 'sidebar-item--active' : '' }} hover:bg-blue-50 hover:text-blue-700 rounded-xl transition-all duration-200 group">
                <div class="menu-icon-box w-10 h-10 rounded-lg flex items-center justify-center transition-colors" data-icon="home">
                    <i data-lucide="home" class="w-5 h-5" data-icon="home"></i>
                </div>
                <div class="flex-1">
                    <span class="font-semibold text-lg">Dashboard</span>
                    <p class="text-xs ">Panel principal</p>
                </div>
            </a>
        </div>

        <!-- Gestión Principal (cada ítem según su módulo) -->
        @if($hasPerm($effectivePermissions, 'users') || $hasPerm($effectivePermissions, 'roles') || $hasPerm($effectivePermissions, 'cargos') || $hasPerm($effectivePermissions, 'grupos') || $hasPerm($effectivePermissions, 'fabricantes'))
        @if($isGlobalAdmin || $empresaHasModulo('users') || $empresaHasModulo('roles') || $empresaHasModulo('cargos') || $empresaHasModulo('grupos') || $empresaHasModulo('fabricantes'))
        <div class="relative group">
            <div class="absolute -left-2 top-0 bottom-0 w-1 bg-purple-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <button onclick="toggleSubmenu('gestion')" class="sidebar-item w-full flex items-center justify-between px-4 py-4 hover:bg-purple-50 hover:text-purple-700 rounded-xl transition-all duration-200 group">
                <div class="flex items-center space-x-4">
                    <div class="menu-icon-box w-10 h-10 rounded-lg flex items-center justify-center transition-colors" data-icon="menu">
                        <i data-lucide="menu" class="w-5 h-5" data-icon="menu"></i>
                    </div>
                    <div class="text-left">
                        <span class="font-semibold text-lg">Gestión Principal</span>
                        <p class="text-xs ">Usuarios y roles</p>
                    </div>
                </div>
                <i data-lucide="chevron-down" class="w-5 h-5 transition-transform duration-200" id="gestion-arrow" style="transform: {{ request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('cargos.*') || request()->routeIs('grupos.*') || request()->routeIs('fabricantes.*') ? 'rotate(180deg)' : 'rotate(0deg)' }}"></i>
            </button>
            <div id="gestion-submenu" class="{{ request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('cargos.*') || request()->routeIs('grupos.*') || request()->routeIs('fabricantes.*') ? '' : 'hidden' }} ml-4 mt-2 space-y-2">
                @if($isGlobalAdmin || $empresaHasModulo('users'))
                <a href="{{ route('users.complete') }}" class="sidebar-item flex items-center px-4 py-3 {{ request()->routeIs('users.*') ? 'sidebar-item--active' : '' }} rounded-lg transition-all duration-200">
                    <div class="menu-icon-box menu-icon-box-sub w-7 h-7 rounded-md flex items-center justify-center shrink-0 mr-3" data-icon="users"><i data-lucide="users" class="w-3.5 h-3.5" data-icon="users"></i></div>
                    <span class="font-medium">Usuarios</span>
                </a>
                @endif
                @if($isGlobalAdmin || $empresaHasModulo('roles'))
                <a href="{{ route('roles.complete') }}" class="sidebar-item flex items-center px-4 py-3 {{ request()->routeIs('roles.*') ? 'sidebar-item--active' : '' }} rounded-lg transition-all duration-200">
                    <div class="menu-icon-box menu-icon-box-sub w-7 h-7 rounded-md flex items-center justify-center shrink-0 mr-3" data-icon="shield"><i data-lucide="shield" class="w-3.5 h-3.5" data-icon="shield"></i></div>
                    <span class="font-medium">Roles</span>
                </a>
                @endif
                @if($isGlobalAdmin || $empresaHasModulo('cargos'))
                <a href="{{ route('cargos.complete') }}" class="sidebar-item flex items-center px-4 py-3 {{ request()->routeIs('cargos.*') ? 'sidebar-item--active' : '' }} rounded-lg transition-all duration-200">
                    <div class="menu-icon-box menu-icon-box-sub w-7 h-7 rounded-md flex items-center justify-center shrink-0 mr-3" data-icon="briefcase"><i data-lucide="briefcase" class="w-3.5 h-3.5" data-icon="briefcase"></i></div>
                    <span class="font-medium">Cargos</span>
                </a>
                @endif
                @if($isGlobalAdmin || $empresaHasModulo('grupos'))
                <a href="{{ route('grupos.complete') }}" class="sidebar-item flex items-center px-4 py-3 {{ request()->routeIs('grupos.*') ? 'sidebar-item--active' : '' }} rounded-lg transition-all duration-200">
                    <div class="menu-icon-box menu-icon-box-sub w-7 h-7 rounded-md flex items-center justify-center shrink-0 mr-3" data-icon="layers"><i data-lucide="layers" class="w-3.5 h-3.5" data-icon="layers"></i></div>
                    <span class="font-medium">Grupos</span>
                </a>
                @endif
                @if($isGlobalAdmin || $empresaHasModulo('fabricantes'))
                <a href="{{ route('fabricantes.complete') }}" class="sidebar-item flex items-center px-4 py-3 {{ request()->routeIs('fabricantes.*') ? 'sidebar-item--active' : '' }} rounded-lg transition-all duration-200">
                    <div class="menu-icon-box menu-icon-box-sub w-7 h-7 rounded-md flex items-center justify-center shrink-0 mr-3" data-icon="factory"><i data-lucide="factory" class="w-3.5 h-3.5" data-icon="factory"></i></div>
                    <span class="font-medium">Fabricantes</span>
                </a>
                @endif
            </div>
        </div>
        @endif
        @endif

        <!-- Gestión de Equipos -->
        @if(!$isGlobalOutsideEmpresa && $hasPerm($effectivePermissions, 'equipos') && $empresaHasModulo('equipos'))
        <div class="relative group">
            <div class="absolute -left-2 top-0 bottom-0 w-1 bg-green-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <button onclick="toggleSubmenu('equipos')" class="sidebar-item w-full flex items-center justify-between px-4 py-4 hover:bg-green-50 hover:text-green-700 rounded-xl transition-all duration-200 group">
                <div class="flex items-center space-x-4">
                    <div class="menu-icon-box w-10 h-10 rounded-lg flex items-center justify-center transition-colors" data-icon="monitor">
                        <i data-lucide="monitor" class="w-5 h-5" data-icon="monitor"></i>
                    </div>
                    <div class="text-left">
                        <span class="font-semibold text-lg">Gestión de Equipos</span>
                        <p class="text-xs ">Hardware y dispositivos</p>
                    </div>
                </div>
                <i data-lucide="chevron-down" class="w-5 h-5 transition-transform duration-200" id="equipos-arrow" style="transform: {{ request()->routeIs('equipos.*') || request()->routeIs('tipos.*') || request()->routeIs('clases.*') ? 'rotate(180deg)' : 'rotate(0deg)' }}"></i>
            </button>
            <div id="equipos-submenu" class="{{ request()->routeIs('equipos.*') || request()->routeIs('tipos.*') || request()->routeIs('clases.*') ? '' : 'hidden' }} ml-4 mt-2 space-y-2">
                <a href="{{ route('equipos.index') }}" class="sidebar-item flex items-center px-4 py-3 {{ request()->routeIs('equipos.index') || request()->routeIs('equipos.create') || request()->routeIs('equipos.edit') ? 'sidebar-item--active' : '' }} rounded-lg transition-all duration-200">
                    <div class="menu-icon-box menu-icon-box-sub w-7 h-7 rounded-md flex items-center justify-center shrink-0 mr-3" data-icon="server"><i data-lucide="server" class="w-3.5 h-3.5" data-icon="server"></i></div>
                    <span class="font-medium">Todos los Equipos</span>
                </a>
                <a href="{{ route('tipos.gestion') }}" class="sidebar-item flex items-center px-4 py-3 {{ request()->routeIs('tipos.*') ? 'sidebar-item--active' : '' }} rounded-lg transition-all duration-200">
                    <div class="menu-icon-box menu-icon-box-sub w-7 h-7 rounded-md flex items-center justify-center shrink-0 mr-3" data-icon="layers"><i data-lucide="layers" class="w-3.5 h-3.5" data-icon="layers"></i></div>
                    <span class="font-medium">Tipos de Equipos</span>
                </a>
                @if(!$vistaOficina && $empresaHasModulo('material_didactico'))
                <a href="{{ route('equipos.material-didactico.index') }}" class="sidebar-item flex items-center px-4 py-3 {{ request()->routeIs('equipos.material-didactico.*') ? 'sidebar-item--active' : '' }} rounded-lg transition-all duration-200">
                    <div class="menu-icon-box menu-icon-box-sub w-7 h-7 rounded-md flex items-center justify-center shrink-0 mr-3" data-icon="book-open"><i data-lucide="book-open" class="w-3.5 h-3.5" data-icon="book-open"></i></div>
                    <span class="font-medium">Material Didáctico</span>
                </a>
                @endif
                @if(!$vistaOficina && $empresaHasModulo('equipos_baja'))
                <a href="{{ route('equipos.bajas.index') }}" class="sidebar-item flex items-center px-4 py-3 {{ request()->routeIs('equipos.bajas.*') ? 'sidebar-item--active' : '' }} rounded-lg transition-all duration-200">
                    <div class="menu-icon-box menu-icon-box-sub w-7 h-7 rounded-md flex items-center justify-center shrink-0 mr-3" data-icon="archive"><i data-lucide="archive" class="w-3.5 h-3.5" data-icon="archive"></i></div>
                    <span class="font-medium">Equipos de Baja</span>
                </a>
                @endif
                @if(!$vistaOficina && $empresaHasModulo('auditoria'))
                <a href="{{ route('equipos.auditoria.index') }}" class="sidebar-item flex items-center px-4 py-3 {{ request()->routeIs('equipos.auditoria.*') ? 'sidebar-item--active' : '' }} rounded-lg transition-all duration-200">
                    <div class="menu-icon-box menu-icon-box-sub w-7 h-7 rounded-md flex items-center justify-center shrink-0 mr-3" data-icon="clipboard-check"><i data-lucide="clipboard-check" class="w-3.5 h-3.5" data-icon="clipboard-check"></i></div>
                    <span class="font-medium">Auditoría de Equipos</span>
                </a>
                @endif
            </div>
        </div>
        @endif

        @endif

        <!-- Gestión de Empresa -->
        @if(($hasPerm($effectivePermissions, 'empresa') && $isGlobalAdmin) || ($empresaActiva && ($empresaHasModulo('empresa') || $empresaHasModulo('sede') || $empresaHasModulo('bodega'))))
        <div class="relative group">
            <div class="absolute -left-2 top-0 bottom-0 w-1 bg-orange-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <button onclick="toggleSubmenu('empresa')" class="sidebar-item w-full flex items-center justify-between px-4 py-4 hover:bg-orange-50 hover:text-orange-700 rounded-xl transition-all duration-200 group">
                <div class="flex items-center space-x-4">
                    <div class="menu-icon-box w-10 h-10 rounded-lg flex items-center justify-center transition-colors" data-icon="building">
                        <i data-lucide="building" class="w-5 h-5" data-icon="building"></i>
                    </div>
                    <div class="text-left">
                        <span class="font-semibold text-lg">Gestión de Empresa</span>
                        <p class="text-xs ">Configuración empresarial</p>
                    </div>
                </div>
                <i data-lucide="chevron-down" class="w-5 h-5 transition-transform duration-200" id="empresa-arrow" style="transform: {{ request()->routeIs('empresa.*') ? 'rotate(180deg)' : 'rotate(0deg)' }}"></i>
            </button>
            <div id="empresa-submenu" class="{{ request()->routeIs('empresa.*') ? '' : 'hidden' }} ml-4 mt-2 space-y-2">
                @if($isGlobalAdmin || $empresaHasModulo('empresa'))
                <a href="{{ route('empresa.gestion') }}" class="sidebar-item flex items-center px-4 py-3 {{ request()->routeIs('empresa.*') ? 'sidebar-item--active' : '' }} rounded-lg transition-all duration-200">
                    <div class="menu-icon-box menu-icon-box-sub w-7 h-7 rounded-md flex items-center justify-center shrink-0 mr-3" data-icon="building"><i data-lucide="building" class="w-3.5 h-3.5" data-icon="building"></i></div>
                    <span class="font-medium">Empresa</span>
                </a>
                @endif
                @if($isGlobalAdmin)
                <a href="{{ route('users.complete') }}" class="sidebar-item flex items-center px-4 py-3 {{ request()->routeIs('users.*') ? 'sidebar-item--active' : '' }} rounded-lg transition-all duration-200">
                    <div class="menu-icon-box menu-icon-box-sub w-7 h-7 rounded-md flex items-center justify-center shrink-0 mr-3" data-icon="users"><i data-lucide="users" class="w-3.5 h-3.5" data-icon="users"></i></div>
                    <span class="font-medium">Gestión de usuarios</span>
                </a>
                @endif
                @if($isGlobalAdmin)
                <a href="{{ route('empresa.codigos') }}" class="sidebar-item flex items-center px-4 py-3 {{ request()->routeIs('empresa.codigos*') ? 'sidebar-item--active' : '' }} rounded-lg transition-all duration-200">
                    <div class="menu-icon-box menu-icon-box-sub w-7 h-7 rounded-md flex items-center justify-center shrink-0 mr-3" data-icon="file-text"><i data-lucide="file-text" class="w-3.5 h-3.5" data-icon="file-text"></i></div>
                    <span class="font-medium">Editar códigos</span>
                </a>
                @endif
            </div>
        </div>
        @endif

        <!-- Configuración -->
        @if(!$isGlobalOutsideEmpresa && $hasPerm($effectivePermissions, 'configuracion') && ($empresaHasModulo('exportar') || $empresaHasModulo('hoja_vida') || $empresaHasModulo('inspeccion') || $isGlobalAdmin))
        <div class="relative group">
            <div class="absolute -left-2 top-0 bottom-0 w-1 bg-pink-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <button onclick="toggleSubmenu('config')" class="sidebar-item w-full flex items-center justify-between px-4 py-4 hover:bg-pink-50 hover:text-pink-700 rounded-xl transition-all duration-200 group">
                <div class="flex items-center space-x-4">
                    <div class="menu-icon-box w-10 h-10 rounded-lg flex items-center justify-center transition-colors" data-icon="settings">
                        <i data-lucide="settings" class="w-5 h-5" data-icon="settings"></i>
                    </div>
                    <div class="text-left">
                        <span class="font-semibold text-lg">Configuración</span>
                        <p class="text-xs ">Formatos y exportación</p>
                    </div>
                </div>
                <i data-lucide="chevron-down" class="w-5 h-5 transition-transform duration-200" id="config-arrow" style="transform: {{ request()->routeIs('formatos.*') || request()->routeIs('exportar.*') || request()->routeIs('hoja-vida.*') || request()->routeIs('inspeccion.*') ? 'rotate(180deg)' : 'rotate(0deg)' }}"></i>
            </button>
            <div id="config-submenu" class="{{ request()->routeIs('formatos.*') || request()->routeIs('exportar.*') || request()->routeIs('hoja-vida.*') || request()->routeIs('inspeccion.*') ? '' : 'hidden' }} ml-4 mt-2 space-y-2">
                @if($isGlobalAdmin || $empresaHasModulo('exportar') || $empresaHasModulo('hoja_vida') || $empresaHasModulo('inspeccion'))
                <a href="{{ route('formatos.index') }}" class="sidebar-item flex items-center px-4 py-3 {{ request()->routeIs('formatos.*') ? 'sidebar-item--active' : '' }} rounded-lg transition-all duration-200">
                    <div class="menu-icon-box menu-icon-box-sub w-7 h-7 rounded-md flex items-center justify-center shrink-0 mr-3" data-icon="file-text"><i data-lucide="file-text" class="w-3.5 h-3.5" data-icon="file-text"></i></div>
                    <span class="font-medium">Formatos</span>
                </a>
                @endif
                @if($empresaHasModulo('hoja_vida'))
                <a href="{{ route('hoja-vida.index') }}" class="sidebar-item flex items-center px-4 py-3 {{ request()->routeIs('hoja-vida.*') ? 'sidebar-item--active' : '' }} rounded-lg transition-all duration-200">
                    <div class="menu-icon-box menu-icon-box-sub w-7 h-7 rounded-md flex items-center justify-center shrink-0 mr-3" data-icon="file-text"><i data-lucide="file-text" class="w-3.5 h-3.5" data-icon="file-text"></i></div>
                    <span class="font-medium">Hoja de Vida</span>
                </a>
                @endif
                @if($empresaHasModulo('inspeccion'))
                <a href="{{ route('inspeccion.index') }}" class="sidebar-item flex items-center px-4 py-3 {{ request()->routeIs('inspeccion.*') ? 'sidebar-item--active' : '' }} rounded-lg transition-all duration-200">
                    <div class="menu-icon-box menu-icon-box-sub w-7 h-7 rounded-md flex items-center justify-center shrink-0 mr-3" data-icon="file-text"><i data-lucide="file-text" class="w-3.5 h-3.5" data-icon="file-text"></i></div>
                    <span class="font-medium">Inspección</span>
                </a>
                @endif
                @if($empresaHasModulo('exportar'))
                <a href="{{ route('exportar.index') }}" class="sidebar-item flex items-center px-4 py-3 {{ request()->routeIs('exportar.*') ? 'sidebar-item--active' : '' }} rounded-lg transition-all duration-200">
                    <div class="menu-icon-box menu-icon-box-sub w-7 h-7 rounded-md flex items-center justify-center shrink-0 mr-3" data-icon="download"><i data-lucide="download" class="w-3.5 h-3.5" data-icon="download"></i></div>
                    <span class="font-medium">Exportar</span>
                </a>
                @endif
            </div>
        </div>
        @endif

        <!-- Asignar -->
        @if(!$isGlobalOutsideEmpresa && $hasPerm($effectivePermissions, 'asignar') && $empresaHasModulo('asignar'))
        <div class="relative group">
            <div class="absolute -left-2 top-0 bottom-0 w-1 bg-teal-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <a href="{{ route('asignar.index') }}" class="sidebar-item w-full flex items-center px-4 py-4 {{ request()->routeIs('asignar.*') ? 'sidebar-item--active' : '' }} rounded-xl transition-all duration-200 group">
                <div class="flex items-center space-x-4">
                    <div class="menu-icon-box w-10 h-10 rounded-lg flex items-center justify-center transition-colors" data-icon="user-check">
                        <i data-lucide="user-check" class="w-5 h-5" data-icon="user-check"></i>
                    </div>
                    <div class="text-left">
                        <span class="font-semibold text-lg">Asignar</span>
                        <p class="text-xs ">Asignación de recursos</p>
                    </div>
                </div>
            </a>
        </div>
        @if($vistaOficina)
        <div class="relative group">
            <div class="absolute -left-2 top-0 bottom-0 w-1 bg-emerald-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <a href="{{ route('asignar.mis-cosas') }}" class="sidebar-item w-full flex items-center px-4 py-4 {{ request()->routeIs('asignar.mis-cosas') ? 'sidebar-item--active' : '' }} rounded-xl transition-all duration-200 group">
                <div class="flex items-center space-x-4">
                    <div class="menu-icon-box w-10 h-10 rounded-lg flex items-center justify-center transition-colors" data-icon="package-check">
                        <i data-lucide="package-check" class="w-5 h-5" data-icon="package-check"></i>
                    </div>
                    <div class="text-left">
                        <span class="font-semibold text-lg">Mis cosas</span>
                        <p class="text-xs ">Mis equipos y actas</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="relative group">
            <div class="absolute -left-2 top-0 bottom-0 w-1 bg-cyan-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <a href="{{ route('asignar.seguimiento') }}" class="sidebar-item w-full flex items-center px-4 py-4 {{ request()->routeIs('asignar.seguimiento') ? 'sidebar-item--active' : '' }} rounded-xl transition-all duration-200 group">
                <div class="flex items-center space-x-4">
                    <div class="menu-icon-box w-10 h-10 rounded-lg flex items-center justify-center transition-colors" data-icon="file-check-2">
                        <i data-lucide="file-check-2" class="w-5 h-5" data-icon="file-check-2"></i>
                    </div>
                    <div class="text-left">
                        <span class="font-semibold text-lg">Seguimiento formatos</span>
                        <p class="text-xs ">Control de asignaciones</p>
                    </div>
                </div>
            </a>
        </div>
        @endif
        @endif

        @if(!$isGlobalOutsideEmpresa && $empresaHasModulo('prestamos_temporales'))
        <div class="relative group">
            <div class="absolute -left-2 top-0 bottom-0 w-1 bg-violet-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <a href="{{ route('prestamos-temporales.index') }}" class="sidebar-item w-full flex items-center px-4 py-4 {{ request()->routeIs('prestamos-temporales.*') ? 'sidebar-item--active' : '' }} rounded-xl transition-all duration-200 group">
                <div class="flex items-center space-x-4">
                    <div class="menu-icon-box w-10 h-10 rounded-lg flex items-center justify-center transition-colors" data-icon="repeat">
                        <i data-lucide="repeat" class="w-5 h-5" data-icon="repeat"></i>
                    </div>
                    <div class="text-left">
                        <span class="font-semibold text-lg">Prestamos temporales</span>
                        <p class="text-xs ">Prestar y devolver equipos</p>
                    </div>
                </div>
            </a>
        </div>
        @endif

        <div class="border-t border-gray-200 pt-4 mt-4">
            <p class="text-xs font-semibold  uppercase tracking-wider mb-3 px-4">Sistema</p>

            <div class="relative group">
                <div class="absolute -left-2 top-0 bottom-0 w-1 bg-indigo-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <a href="{{ route('gemini.index') }}" class="sidebar-item w-full flex items-center px-4 py-4 {{ request()->routeIs('gemini.*') ? 'sidebar-item--active' : '' }} rounded-xl transition-all duration-200 group">
                    <div class="flex items-center space-x-4">
<div class="menu-icon-box w-10 h-10 rounded-lg flex items-center justify-center transition-colors" data-icon="sparkles">
                        <i data-lucide="sparkles" class="w-5 h-5" data-icon="sparkles"></i>
                    </div>
                        <div class="text-left">
                            <span class="font-semibold text-lg">Asistente</span>
                            <p class="text-xs ">Pregunta por SAMS (voz y texto)</p>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="relative group">
                <div class="absolute -left-2 top-0 bottom-0 w-1 bg-gray-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <a href="{{ route('profile.show') }}" class="sidebar-item w-full flex items-center px-4 py-4 {{ request()->routeIs('profile.*') ? 'sidebar-item--active' : '' }} rounded-xl transition-all duration-200 group">
                    <div class="flex items-center space-x-4">
                        <div class="menu-icon-box w-10 h-10 rounded-lg flex items-center justify-center transition-colors" data-icon="user">
                            <i data-lucide="user" class="w-5 h-5" data-icon="user"></i>
                        </div>
                        <div class="text-left">
                            <span class="font-semibold text-lg">Mi Perfil</span>
                            <p class="text-xs ">Información personal</p>
                        </div>
                    </div>
                </a>
            </div>
            
            <form action="{{ route('logout') }}" method="POST" class="relative group">
                @csrf
                <button type="submit" class="sidebar-item w-full flex items-center px-4 py-4 text-red-600 hover:bg-red-50 hover:text-red-700 rounded-xl transition-all duration-200 group">
                    <div class="flex items-center space-x-4">
                        <div class="menu-icon-box w-10 h-10 rounded-lg flex items-center justify-center transition-colors" data-icon="log-out">
                            <i data-lucide="log-out" class="w-5 h-5" data-icon="log-out"></i>
                        </div>
                        <div class="text-left">
                            <span class="font-semibold text-lg">Cerrar Sesión</span>
                            <p class="text-xs ">Salir del sistema</p>
                        </div>
                    </div>
                </button>
            </form>

            @if($canToggleOffice && $empresaActivaEsPreventionWorld)
                @if(!$modoOficinaSesion)
                    <form action="{{ route('modo-oficina.entrar') }}" method="POST" class="relative group">
                        @csrf
                        <button type="submit" class="sidebar-item w-full flex items-center px-4 py-4 text-teal-700 hover:bg-teal-50 rounded-xl transition-all duration-200 group">
                            <div class="flex items-center space-x-4">
                                <div class="menu-icon-box w-10 h-10 rounded-lg flex items-center justify-center transition-colors bg-teal-100 text-teal-700" data-icon="building-2">
                                    <i data-lucide="building-2" class="w-5 h-5"></i>
                                </div>
                                <div class="text-left">
                                    <span class="font-semibold text-lg">Oficina</span>
                                    <p class="text-xs text-teal-600">Apartado Prevention World</p>
                                </div>
                            </div>
                        </button>
                    </form>
                @else
                    <form action="{{ route('modo-oficina.salir') }}" method="POST" class="relative group">
                        @csrf
                        <button type="submit" class="sidebar-item w-full flex items-center px-4 py-4 text-sky-800 hover:bg-sky-50 rounded-xl transition-all duration-200 group">
                            <div class="flex items-center space-x-4">
                                <div class="menu-icon-box w-10 h-10 rounded-lg flex items-center justify-center transition-colors bg-sky-100 text-sky-800" data-icon="undo-2">
                                    <i data-lucide="undo-2" class="w-5 h-5"></i>
                                </div>
                                <div class="text-left">
                                    <span class="font-semibold text-lg">Prevention World</span>
                                    <p class="text-xs text-sky-700">Volver a SAMS completo</p>
                                </div>
                            </div>
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </nav>
</aside>

<div id="safetyRunnerModal" class="fixed inset-0 hidden items-center justify-center z-[120] bg-black/70 p-4">
    <div class="w-full max-w-4xl rounded-2xl border border-cyan-300/30 bg-[#071a33]/95 shadow-[0_0_35px_rgba(56,189,248,0.35)] p-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-cyan-100 font-semibold">Mini juego · Safety Runner</h3>
            <div class="flex items-center gap-2">
                <button type="button" id="togglePauseSafetyRunnerBtn" class="px-2 py-1 rounded-md text-xs border border-cyan-300/40 text-cyan-100 hover:bg-cyan-300/20">Pausa</button>
                <button type="button" id="closeSafetyRunnerBtn" class="px-2 py-1 rounded-md text-xs border border-cyan-300/40 text-cyan-100 hover:bg-cyan-300/20">Cerrar</button>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-3 text-xs">
            <label class="text-cyan-200">
                Personaje
                <select id="runnerCharacter" class="mt-1 w-full rounded-md bg-[#0b2748] border border-cyan-300/30 text-cyan-100 px-2 py-1">
                    <option value="man">Hombre</option>
                    <option value="woman">Mujer</option>
                    <option value="dog">Perro</option>
                    <option value="cat">Gato</option>
                </select>
            </label>
            <label class="text-cyan-200">
                Color
                <select id="runnerColor" class="mt-1 w-full rounded-md bg-[#0b2748] border border-cyan-300/30 text-cyan-100 px-2 py-1">
                    <option value="#67e8f9">Cyan</option>
                    <option value="#93c5fd">Azul claro</option>
                    <option value="#f0abfc">Morado</option>
                    <option value="#fca5a5">Rojo suave</option>
                </select>
            </label>
            <div class="text-cyan-200 flex items-end">W / Espacio / ↑ = Saltar</div>
            <div class="text-cyan-200 flex items-end">S / ↓ = Agacharse</div>
        </div>
        <canvas id="safetyRunnerCanvas" width="980" height="340" class="w-full rounded-xl bg-[#061326] border border-cyan-300/20"></canvas>
        <div class="mt-2 text-xs text-cyan-200/90 flex items-center justify-between">
            <span>Objetos: ⛑️/🦺 = inmunidad · 🧯 = vuelo · 👢 = salto alto</span>
            <button type="button" id="restartSafetyRunnerBtn" class="px-2 py-1 rounded-md border border-cyan-300/40 hover:bg-cyan-300/20">Reiniciar</button>
        </div>
    </div>
</div>

<script>
(function () {
    const openBtn = document.getElementById('openSafetyRunnerBtn');
    const closeBtn = document.getElementById('closeSafetyRunnerBtn');
    const restartBtn = document.getElementById('restartSafetyRunnerBtn');
    const pauseBtn = document.getElementById('togglePauseSafetyRunnerBtn');
    const characterSelect = document.getElementById('runnerCharacter');
    const colorSelect = document.getElementById('runnerColor');
    const modal = document.getElementById('safetyRunnerModal');
    const canvas = document.getElementById('safetyRunnerCanvas');
    if (!openBtn || !closeBtn || !modal || !canvas) return;

    const ctx = canvas.getContext('2d');
    let raf = null;
    let running = false;
    let paused = false;
    let gameOver = false;
    let score = 0;
    let obstaclePassed = 0;
    let speed = 180; // px/s base
    let timeScale = 1;
    let lastTs = 0;
    let elapsed = 0;
    let spawnObstacleAt = 1.1;
    let spawnPickupAt = 3.2;
    let immunityUntil = 0;
    let flyUntil = 0;
    let bootUntil = 0;
    let crouching = false;
    let shake = 0;
    const obstacles = [];
    const pickups = [];
    const particles = [];
    const stars = Array.from({ length: 80 }, () => ({
        x: Math.random() * canvas.width,
        y: Math.random() * canvas.height,
        r: Math.random() * 1.8 + 0.6,
        a: Math.random() * 0.5 + 0.2
    }));
    const runner = {
        x: 78, y: 232, w: 48, h: 48, vy: 0, jump: -460, gravity: 1400,
        type: 'man', color: '#67e8f9', frame: 0, duckLerp: 0
    };
    const groundY = 280;
    const pickupCycle = ['boots', 'extinguisher', 'helmet', 'harness'];
    let pickupCursor = 0;

    function rand(min, max) { return Math.random() * (max - min) + min; }
    function nowMs() { return performance.now(); }
    function active(until) { return nowMs() < until; }
    function collide(a, b) { return a.x < b.x + b.w && a.x + a.w > b.x && a.y < b.y + b.h && a.y + a.h > b.y; }

    function spawnFx(x, y, color, count) {
        for (let i = 0; i < count; i++) {
            particles.push({ x, y, vx: rand(-120, 120), vy: rand(-140, -20), life: rand(0.4, 0.9), t: 0, c: color });
        }
    }

    function resetGame() {
        score = 0;
        obstaclePassed = 0;
        speed = 180;
        timeScale = 1;
        elapsed = 0;
        spawnObstacleAt = 1.0;
        spawnPickupAt = 3.2;
        immunityUntil = 0;
        flyUntil = 0;
        bootUntil = 0;
        crouching = false;
        paused = false;
        pauseBtn.textContent = 'Pausa';
        gameOver = false;
        shake = 0;
        runner.type = characterSelect?.value || 'man';
        runner.color = colorSelect?.value || '#67e8f9';
        runner.y = 232;
        runner.vy = 0;
        runner.frame = 0;
        runner.duckLerp = 0;
        obstacles.length = 0;
        pickups.length = 0;
        particles.length = 0;
        pickupCursor = 0;
        lastTs = 0;
    }

    function spawnObstacle() {
        const kinds = [
            { type: 'rock', emoji: '🪨', w: 34, h: 28, y: groundY - 28 },
            { type: 'bird', emoji: '🐦', w: 32, h: 24, y: 206 },
            { type: 'block', emoji: '🧱', w: 30, h: 30, y: groundY - 30 }
        ];
        const k = kinds[(Math.random() * kinds.length) | 0];
        obstacles.push({ ...k, x: canvas.width + 20, passed: false, anim: 0 });
        spawnObstacleAt = elapsed + rand(0.9, 1.7);
    }

    function spawnPickup() {
        if (pickups.length >= 3) {
            spawnPickupAt = elapsed + rand(2.8, 4.5);
            return;
        }
        const type = pickupCycle[pickupCursor % pickupCycle.length];
        pickupCursor += 1;
        const byType = {
            helmet: { type: 'helmet', emoji: '⛑️' },
            harness: { type: 'harness', emoji: '🦺' },
            extinguisher: { type: 'extinguisher', emoji: '🧯' },
            boots: { type: 'boots', emoji: '👢' }
        };
        const pick = byType[type];
        pickups.push({ ...pick, x: canvas.width + 20, y: rand(188, 230), w: 26, h: 26, bob: rand(0, 6.28) });
        spawnPickupAt = elapsed + rand(3.3, 5.2);
    }

    function update(dt) {
        elapsed += dt;
        speed = Math.min(460, speed + 12 * dt); // progresión más rápida
        timeScale = speed / 180;

        const duckTarget = crouching ? 1 : 0;
        runner.duckLerp += (duckTarget - runner.duckLerp) * Math.min(1, dt * 12);

        if (active(flyUntil)) {
            runner.vy += (-220 - runner.vy) * Math.min(1, dt * 7);
        } else {
            runner.vy += runner.gravity * dt;
        }
        runner.y += runner.vy * dt;
        const floorY = 232 + runner.duckLerp * 18;
        if (runner.y > floorY) { runner.y = floorY; runner.vy = 0; }
        if (runner.y < 110) runner.y = 110;
        runner.frame += dt * 12;

        if (elapsed >= spawnObstacleAt) spawnObstacle();
        if (elapsed >= spawnPickupAt) spawnPickup();

        const move = speed * dt;
        obstacles.forEach(o => {
            o.x -= move;
            o.anim += dt * 10;
            if (o.type === 'bird') o.y += Math.sin(o.anim * 2.4) * 0.9;
        });
        pickups.forEach(p => {
            p.x -= move;
            p.bob += dt * 4;
            p.y += Math.sin(p.bob) * 0.6;
        });

        for (let i = obstacles.length - 1; i >= 0; i--) if (obstacles[i].x < -80) obstacles.splice(i, 1);
        for (let i = pickups.length - 1; i >= 0; i--) if (pickups[i].x < -80) pickups.splice(i, 1);

        for (const o of obstacles) {
            if (!o.passed && (o.x + o.w) < runner.x) {
                o.passed = true;
                obstaclePassed += 1;
                score += 10;
            }
            if (collide(runner, o)) {
                const petMode = runner.type === 'dog' || runner.type === 'cat';
                if (o.type === 'bird' && petMode) {
                    score += 14;
                    spawnFx(o.x, o.y, '#7dd3fc', 12);
                    o.x = -100;
                    continue;
                }
                if (active(immunityUntil)) {
                    score += 6;
                    spawnFx(o.x, o.y, '#67e8f9', 10);
                    o.x = -100;
                    continue;
                }
                gameOver = true;
                running = false;
                shake = 10;
                spawnFx(runner.x + 18, runner.y + 12, '#fca5a5', 24);
                break;
            }
        }

        for (let i = pickups.length - 1; i >= 0; i--) {
            const p = pickups[i];
            if (!collide(runner, p)) continue;
            const t = nowMs();
            if (p.type === 'helmet' || p.type === 'harness') immunityUntil = t + 6500;
            if (p.type === 'extinguisher') flyUntil = t + 6000;
            if (p.type === 'boots') bootUntil = t + 15000;
            score += 18;
            spawnFx(p.x, p.y, '#bae6fd', 14);
            pickups.splice(i, 1);
        }

        for (let i = particles.length - 1; i >= 0; i--) {
            const p = particles[i];
            p.t += dt;
            p.x += p.vx * dt;
            p.y += p.vy * dt;
            p.vy += 220 * dt;
            if (p.t >= p.life) particles.splice(i, 1);
        }
        if (shake > 0) shake *= 0.9;
    }

    function draw() {
        ctx.save();
        const sx = (Math.random() - 0.5) * shake;
        const sy = (Math.random() - 0.5) * shake;
        ctx.translate(sx, sy);

        const g = ctx.createLinearGradient(0, 0, 0, canvas.height);
        g.addColorStop(0, '#06182f');
        g.addColorStop(1, '#081f3e');
        ctx.fillStyle = g;
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        ctx.globalAlpha = 0.8;
        stars.forEach(s => {
            ctx.fillStyle = `rgba(186,230,253,${s.a})`;
            ctx.beginPath();
            ctx.arc((s.x - (elapsed * 30) % canvas.width + canvas.width) % canvas.width, s.y, s.r, 0, Math.PI * 2);
            ctx.fill();
        });
        ctx.globalAlpha = 1;

        ctx.strokeStyle = 'rgba(125,211,252,0.34)';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(0, groundY + 40);
        ctx.lineTo(canvas.width, groundY + 40);
        ctx.stroke();

        // Runner dibujado de perfil (sin usar emoji "de espalda")
        ctx.save();
        const rx = runner.x + 10;
        const ry = runner.y + 8;
        const bob = Math.sin(runner.frame * 2.8) * 2;
        const isPet = runner.type === 'dog' || runner.type === 'cat';
        const bodyColor = runner.color;
        ctx.shadowColor = bodyColor;
        ctx.shadowBlur = active(immunityUntil) ? 20 : 8;
        ctx.fillStyle = bodyColor;
        if (isPet) {
            // cuerpo mascota
            ctx.fillRect(rx + 4, ry + 14 + bob, 24, 12);
            ctx.fillRect(rx + 24, ry + 10 + bob, 10, 10); // cabeza
            ctx.fillRect(rx + 2, ry + 22 + bob, 6, 8);   // pata
            ctx.fillRect(rx + 12, ry + 22 + bob + ((runner.frame | 0) % 2 ? 2 : -1), 6, 8);
            ctx.fillRect(rx + 22, ry + 22 + bob, 6, 8);
            ctx.fillRect(rx - 2, ry + 12 + bob, 6, 3); // cola
            if (runner.type === 'cat') { // orejas
                ctx.fillRect(rx + 25, ry + 7 + bob, 2, 3);
                ctx.fillRect(rx + 30, ry + 7 + bob, 2, 3);
            }
        } else {
            // cuerpo persona lateral
            ctx.fillRect(rx + 8, ry + 10 + bob, 10, 14); // torso
            ctx.fillRect(rx + 16, ry + 12 + bob, 7, 5);  // brazo frontal
            ctx.fillRect(rx + 9, ry + 24 + bob, 4, 12);  // pierna 1
            ctx.fillRect(rx + 14, ry + 24 + bob + ((runner.frame | 0) % 2 ? 3 : -2), 4, 12); // pierna 2 animada
            ctx.beginPath(); // cabeza
            ctx.arc(rx + 12, ry + 6 + bob, 5, 0, Math.PI * 2);
            ctx.fill();
            if (runner.type === 'woman') {
                ctx.fillRect(rx + 6, ry + 2 + bob, 4, 2); // cabello lateral
            }
        }
        ctx.restore();

        ctx.font = '22px sans-serif';
        obstacles.forEach(o => {
            const e = o.type === 'bird' ? ((o.anim | 0) % 2 ? '🕊️' : '🐦') : o.emoji;
            ctx.fillText(e, o.x, o.y + o.h);
        });
        pickups.forEach(p => ctx.fillText(p.emoji, p.x, p.y + p.h));

        particles.forEach(p => {
            const a = 1 - p.t / p.life;
            ctx.fillStyle = `rgba(186,230,253,${a})`;
            ctx.fillRect(p.x, p.y, 3, 3);
        });

        ctx.fillStyle = '#dbeafe';
        ctx.font = 'bold 15px Inter, sans-serif';
        ctx.fillText(`Puntos: ${Math.floor(score)}  ·  Obstáculos: ${obstaclePassed}  ·  Velocidad: ${Math.floor(speed)}`, 14, 24);
        if (active(immunityUntil)) ctx.fillText('ESCUDO', 14, 44);
        if (active(flyUntil)) ctx.fillText('VUELO', 98, 44);
        if (active(bootUntil)) ctx.fillText('BOTAS+', 160, 44);
        if (paused) {
            ctx.fillStyle = '#a5f3fc';
            ctx.font = 'bold 26px Inter, sans-serif';
            ctx.fillText('PAUSA', canvas.width / 2 - 46, 88);
        }
        if (gameOver) {
            ctx.fillStyle = '#fef3c7';
            ctx.font = 'bold 22px Inter, sans-serif';
            ctx.fillText('Fin del juego · Reinicia para continuar', canvas.width / 2 - 190, 122);
        }

        ctx.restore();
    }

    function loop(ts) {
        if (!running) return;
        if (!lastTs) lastTs = ts;
        const dt = Math.min(0.033, (ts - lastTs) / 1000);
        lastTs = ts;
        if (!paused && !gameOver) update(dt);
        draw();
        raf = requestAnimationFrame(loop);
    }

    function jump() {
        if (!running || gameOver || paused) return;
        const jumpPower = active(bootUntil) ? -680 : runner.jump;
        if (runner.y >= 231 || active(flyUntil)) runner.vy = jumpPower;
    }

    function setCrouch(on) {
        crouching = !!on;
    }

    function togglePause() {
        if (!running || gameOver) return;
        paused = !paused;
        pauseBtn.textContent = paused ? 'Reanudar' : 'Pausa';
    }

    function openGame() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        resetGame();
        running = true;
        if (typeof lucide !== 'undefined') lucide.createIcons();
        cancelAnimationFrame(raf);
        raf = requestAnimationFrame(loop);
    }

    function closeGame() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        running = false;
        cancelAnimationFrame(raf);
    }

    openBtn.addEventListener('click', openGame);
    closeBtn.addEventListener('click', closeGame);
    pauseBtn?.addEventListener('click', togglePause);
    restartBtn?.addEventListener('click', function () {
        resetGame();
        running = true;
        cancelAnimationFrame(raf);
        raf = requestAnimationFrame(loop);
    });
    characterSelect?.addEventListener('change', function () { runner.type = characterSelect.value; });
    colorSelect?.addEventListener('change', function () { runner.color = colorSelect.value; });
    canvas.addEventListener('click', jump);
    window.addEventListener('keydown', function (e) {
        if (!modal.classList.contains('flex')) return;
        if (e.key === 'p' || e.key === 'P') { e.preventDefault(); togglePause(); }
        if (e.code === 'Space' || e.code === 'ArrowUp' || e.key === 'w' || e.key === 'W') {
            e.preventDefault();
            jump();
        }
        if (e.code === 'ArrowDown' || e.key === 's' || e.key === 'S') {
            e.preventDefault();
            setCrouch(true);
        }
    });
    window.addEventListener('keyup', function (e) {
        if (!modal.classList.contains('flex')) return;
        if (e.code === 'ArrowDown' || e.key === 's' || e.key === 'S') {
            setCrouch(false);
        }
    });
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeGame();
    });
})();
</script>
