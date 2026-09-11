@php
    $user = Auth::user();
    $rolePermissions = Auth::user()->role?->permissions ?? null;
    $effectivePermissions = ($user && $user->empresa_id) ? null : $rolePermissions;
    $isPreventionWorldAdmin = $user && !$user->empresa_id && (strtolower(trim($user->role?->name ?? '')) === 'administrador');
    $hasPerm = function (?array $perms, string $key): bool {
        if (empty($perms) || !is_array($perms)) return true;
        return in_array($key, $perms, true);
    };
    $empresaActiva = \App\Services\EmpresaContext::empresaActiva();
    $empresaModulosRaw = $empresaActiva?->modulos ?? null;
    $empresaModuloNivel = function (string $key) use ($empresaModulosRaw, $isPreventionWorldAdmin, $user): string {
        if ($isPreventionWorldAdmin) return 'edit';
        if ($empresaModulosRaw === null || !is_array($empresaModulosRaw)) {
            return ($user && $user->empresa_id) ? 'none' : 'edit';
        }
        if (array_is_list($empresaModulosRaw)) return in_array($key, $empresaModulosRaw, true) ? 'edit' : 'none';
        $val = $empresaModulosRaw[$key] ?? null;
        if ($val === null && in_array($key, ['users', 'roles', 'cargos', 'grupos', 'fabricantes'], true)) {
            $val = $empresaModulosRaw['gestion_principal'] ?? null;
        }
        if (in_array($val, ['none', 'view', 'edit'], true)) return $val;
        return $val ? 'edit' : 'none';
    };
    $empresaHasModulo = function (string $key) use ($empresaModuloNivel): bool {
        return $empresaModuloNivel($key) !== 'none';
    };
@endphp
<nav id="topMenu" class="pw-menu-top pw-menu-top--brand flex-shrink-0 border-b border-gray-200/50 overflow-visible">
    <div class="flex items-center gap-1 px-4 py-2 min-w-max">
        <a href="{{ route('sugerencias.index') }}" class="top-menu-item inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('sugerencias.*') ? 'top-menu-item--active' : '' }}" >
            <i data-lucide="message-square" class="w-4 h-4 relative" data-icon="message-square"></i>
            <span>Sugerencias</span>
        </a>
        <a href="{{ route('admin.dashboard') }}" class="top-menu-item inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'top-menu-item--active' : '' }}" >
            <i data-lucide="home" class="w-4 h-4" data-icon="home"></i>
            <span>Dashboard</span>
        </a>
        @if(($hasPerm($effectivePermissions, 'users') || $hasPerm($effectivePermissions, 'roles') || $hasPerm($effectivePermissions, 'cargos') || $hasPerm($effectivePermissions, 'grupos') || $hasPerm($effectivePermissions, 'fabricantes')) && ($empresaHasModulo('users') || $empresaHasModulo('roles') || $empresaHasModulo('cargos') || $empresaHasModulo('grupos') || $empresaHasModulo('fabricantes') || $isPreventionWorldAdmin || (!$user?->empresa_id)))
        <div class="top-menu-dropdown relative group" data-dropdown>
            <button type="button" class="top-menu-item inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('cargos.*') || request()->routeIs('grupos.*') || request()->routeIs('fabricantes.*') ? 'top-menu-item--active' : '' }}"  aria-expanded="false" aria-haspopup="true">
                <i data-lucide="menu" class="w-4 h-4" data-icon="menu"></i>
                <span>Gestión Principal</span>
                <i data-lucide="chevron-down" class="w-4 h-4"></i>
            </button>
            <div class="top-dropdown-panel absolute left-0 top-full mt-1 py-2 bg-white rounded-xl shadow-xl border border-gray-200 min-w-[180px] z-[100] hidden group-hover:block group-[.dropdown-open]:block">
                @if($empresaHasModulo('users') || $isPreventionWorldAdmin || (!$user?->empresa_id))<a href="{{ route('users.complete') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i data-lucide="users" class="w-4 h-4" data-icon="users"></i> Usuarios</a>@endif
                @if($empresaHasModulo('roles') || $isPreventionWorldAdmin || (!$user?->empresa_id))<a href="{{ route('roles.complete') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i data-lucide="shield" class="w-4 h-4" data-icon="shield"></i> Roles</a>@endif
                @if($empresaHasModulo('cargos') || $isPreventionWorldAdmin || (!$user?->empresa_id))<a href="{{ route('cargos.complete') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i data-lucide="briefcase" class="w-4 h-4" data-icon="briefcase"></i> Cargos</a>@endif
                @if($empresaHasModulo('grupos') || $isPreventionWorldAdmin || (!$user?->empresa_id))<a href="{{ route('grupos.complete') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i data-lucide="layers" class="w-4 h-4" data-icon="layers"></i> Grupos</a>@endif
                @if($empresaHasModulo('fabricantes') || $isPreventionWorldAdmin || (!$user?->empresa_id))<a href="{{ route('fabricantes.complete') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i data-lucide="factory" class="w-4 h-4" data-icon="factory"></i> Fabricantes</a>@endif
            </div>
        </div>
        @endif
        @if($hasPerm($effectivePermissions, 'equipos') && ($empresaHasModulo('equipos') || $isPreventionWorldAdmin))
        <div class="top-menu-dropdown relative group" data-dropdown>
            <button type="button" class="top-menu-item inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('equipos.*') || request()->routeIs('tipos.*') || request()->routeIs('clases.*') ? 'top-menu-item--active' : '' }}"  aria-expanded="false" aria-haspopup="true">
                <i data-lucide="monitor" class="w-4 h-4" data-icon="monitor"></i>
                <span>Equipos</span>
                <i data-lucide="chevron-down" class="w-4 h-4"></i>
            </button>
            <div class="top-dropdown-panel absolute left-0 top-full mt-1 py-2 bg-white rounded-xl shadow-xl border border-gray-200 min-w-[180px] z-[100] hidden group-hover:block group-[.dropdown-open]:block">
                <a href="{{ route('equipos.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i data-lucide="server" class="w-4 h-4" data-icon="server"></i> Todos los Equipos</a>
                <a href="{{ route('tipos.gestion') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i data-lucide="layers" class="w-4 h-4" data-icon="layers"></i> Tipos</a>
                @if($empresaHasModulo('material_didactico') || $empresaHasModulo('equipos_baja') || $empresaHasModulo('auditoria') || $isPreventionWorldAdmin)
                @if($empresaHasModulo('material_didactico') || $isPreventionWorldAdmin)<a href="{{ route('equipos.material-didactico.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i data-lucide="book-open" class="w-4 h-4" data-icon="book-open"></i> Material Didáctico</a>@endif
                @if($empresaHasModulo('equipos_baja') || $isPreventionWorldAdmin)<a href="{{ route('equipos.bajas.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i data-lucide="archive" class="w-4 h-4" data-icon="archive"></i> Equipos de Baja</a>@endif
                @if($empresaHasModulo('auditoria') || $isPreventionWorldAdmin)<a href="{{ route('equipos.auditoria.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i data-lucide="clipboard-check" class="w-4 h-4" data-icon="clipboard-check"></i> Auditoría</a>@endif
                @endif
            </div>
        </div>
        @endif
        @if($hasPerm($effectivePermissions, 'empresa') && ($empresaHasModulo('empresa') || $empresaHasModulo('sede') || $empresaHasModulo('bodega') || $isPreventionWorldAdmin))
        <div class="top-menu-dropdown relative group" data-dropdown>
            <button type="button" class="top-menu-item inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('empresa.*') ? 'top-menu-item--active' : '' }}"  aria-expanded="false" aria-haspopup="true">
                <i data-lucide="building" class="w-4 h-4" data-icon="building"></i>
                <span>Empresa</span>
                <i data-lucide="chevron-down" class="w-4 h-4"></i>
            </button>
            <div class="top-dropdown-panel absolute left-0 top-full mt-1 py-2 bg-white rounded-xl shadow-xl border border-gray-200 min-w-[180px] z-[100] hidden group-hover:block group-[.dropdown-open]:block">
                <a href="{{ route('empresa.gestion') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i data-lucide="building" class="w-4 h-4" data-icon="building"></i> Empresa</a>
                @if($isPreventionWorldAdmin)
                <a href="{{ route('users.complete') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i data-lucide="users" class="w-4 h-4" data-icon="users"></i> Gestión de usuarios</a>
                <a href="{{ route('empresa.codigos') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i data-lucide="file-text" class="w-4 h-4" data-icon="file-text"></i> Editar códigos</a>
                @endif
            </div>
        </div>
        @endif
        @if($hasPerm($effectivePermissions, 'configuracion') && ($empresaHasModulo('exportar') || $empresaHasModulo('hoja_vida') || $empresaHasModulo('inspeccion') || $isPreventionWorldAdmin))
        <div class="top-menu-dropdown relative group" data-dropdown>
            <button type="button" class="top-menu-item inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('formatos.*') || request()->routeIs('exportar.*') || request()->routeIs('hoja-vida.*') || request()->routeIs('inspeccion.*') ? 'top-menu-item--active' : '' }}"  aria-expanded="false" aria-haspopup="true">
                <i data-lucide="settings" class="w-4 h-4" data-icon="settings"></i>
                <span>Configuración</span>
                <i data-lucide="chevron-down" class="w-4 h-4"></i>
            </button>
            <div class="top-dropdown-panel absolute left-0 top-full mt-1 py-2 bg-white rounded-xl shadow-xl border border-gray-200 min-w-[180px] z-[100] hidden group-hover:block group-[.dropdown-open]:block">
                @if($empresaHasModulo('exportar') || $empresaHasModulo('hoja_vida') || $empresaHasModulo('inspeccion') || $isPreventionWorldAdmin)<a href="{{ route('formatos.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i data-lucide="file-text" class="w-4 h-4" data-icon="file-text"></i> Formatos</a>@endif
                @if($empresaHasModulo('inspeccion') || $isPreventionWorldAdmin)<a href="{{ route('inspeccion.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i data-lucide="file-text" class="w-4 h-4" data-icon="file-text"></i> Inspección</a>@endif
                @if($empresaHasModulo('exportar') || $isPreventionWorldAdmin)<a href="{{ route('exportar.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i data-lucide="download" class="w-4 h-4" data-icon="download"></i> Exportar</a>@endif
            </div>
        </div>
        @endif
        @if($hasPerm($effectivePermissions, 'asignar') && ($empresaHasModulo('asignar') || $isPreventionWorldAdmin))
        <a href="{{ route('asignar.index') }}" class="top-menu-item inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('asignar.*') ? 'top-menu-item--active' : '' }}" >
            <i data-lucide="user-check" class="w-4 h-4" data-icon="user-check"></i>
            <span>Asignar</span>
        </a>
        @endif
        @if($empresaHasModulo('prestamos_temporales'))
        <a href="{{ route('prestamos-temporales.index') }}" class="top-menu-item inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('prestamos-temporales.*') ? 'top-menu-item--active' : '' }}" >
            <i data-lucide="repeat" class="w-4 h-4" data-icon="repeat"></i>
            <span>Prestamos temporales</span>
        </a>
        @endif
        <a href="{{ route('profile.show') }}" class="top-menu-item inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('profile.*') ? 'top-menu-item--active' : '' }}" >
            <i data-lucide="user" class="w-4 h-4" data-icon="user"></i>
            <span>Mi Perfil</span>
        </a>
        <form action="{{ route('logout') }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="top-menu-item inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-red-300 hover:text-red-200 hover:bg-red-500/20">
                <i data-lucide="log-out" class="w-4 h-4" data-icon="log-out"></i>
                <span>Cerrar Sesión</span>
            </button>
        </form>
    </div>
</nav>
