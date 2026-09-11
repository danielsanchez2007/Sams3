<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Role;
use App\Models\User;
use App\Services\EmpresaContext;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    private function availablePermissionsForEmpresa(?Empresa $empresa): array
    {
        $all = [
            'dashboard' => 'Dashboard principal',
            'users' => 'Gestión de usuarios',
            'roles' => 'Gestión de roles',
            'cargos' => 'Gestión de cargos',
            'grupos' => 'Gestión de grupos',
            'fabricantes' => 'Gestión de fabricantes',
            'equipos' => 'Gestión de equipos',
            'empresa' => 'Gestión de empresa / sedes / bodegas',
            'hoja_vida' => 'Hojas de vida',
            'inspeccion' => 'Inspección',
            'exportar' => 'Exportar / reportes',
            'asignar' => 'Asignar recursos',
            'configuracion' => 'Configuración general',
            'material_didactico' => 'Material didáctico',
            'auditoria' => 'Auditoría',
            'equipos_baja' => 'Equipos de baja',
            'prestamos_temporales' => 'Préstamos temporales',
        ];

        if (! $empresa || ! is_array($empresa->modulos) || empty($empresa->modulos)) {
            return $all;
        }

        $modulos = $empresa->modulos;
        $out = ['dashboard' => $all['dashboard']];
        foreach ($all as $key => $label) {
            if ($key === 'dashboard') {
                continue;
            }
            $nivel = $modulos[$key] ?? null;
            if ($nivel === null && in_array($key, ['users', 'roles', 'cargos', 'grupos', 'fabricantes'], true)) {
                $nivel = $modulos['gestion_principal'] ?? null;
            }
            if ($nivel === null && $key === 'configuracion') {
                $nivel = (($modulos['exportar'] ?? 'none') !== 'none' || ($modulos['hoja_vida'] ?? 'none') !== 'none' || ($modulos['inspeccion'] ?? 'none') !== 'none')
                    ? 'edit'
                    : 'none';
            }
            if (in_array($nivel, ['view', 'edit'], true) || $nivel === true) {
                $out[$key] = $label;
            }
        }

        return $out;
    }

    private function empresaActivaParaScope(): ?Empresa
    {
        $empresaId = EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;

        return $empresaId ? Empresa::find($empresaId) : null;
    }

    private function prefijoEmpresa(Empresa $empresa): string
    {
        $raw = (string) ($empresa->prefijo ?: EmpresaContext::prefijo());
        $pref = strtoupper(preg_replace('/[^A-Z0-9]/', '', $raw) ?: 'EMP');

        return $pref !== '' ? $pref : 'EMP';
    }

    private function nombreRolScoped(string $name, ?Empresa $empresa): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $name) ?? '');
        if (! $empresa) {
            return $clean;
        }

        $pref = $this->prefijoEmpresa($empresa);
        $roleTag = strtoupper((string) ($empresa->code_settings['role_tag'] ?? 'ROL'));
        $roleTag = preg_replace('/[^A-Z0-9]/', '', $roleTag) ?: 'ROL';
        $upper = strtoupper($clean);
        if (str_starts_with($upper, $pref.'-'.$roleTag.'-')) {
            return $clean;
        }
        $clean = preg_replace('/^'.preg_quote($pref, '/').'-/i', '', $clean) ?: $clean;
        $clean = preg_replace('/^'.preg_quote($roleTag, '/').'-/i', '', $clean) ?: $clean;

        return $pref.'-'.$roleTag.'-'.$clean;
    }

    private function assertRoleInScope(Role $role): void
    {
        $empresa = $this->empresaActivaParaScope();
        if (!$empresa) {
            return;
        }

        $prefijo = $this->prefijoEmpresa($empresa);
        $name = strtoupper((string) $role->name);
        $allowed = str_starts_with($name, $prefijo.'-')
            || ((int) $role->id === (int) $empresa->default_role_id);

        abort_unless($allowed && strtolower(trim((string) $role->name)) !== 'administrador', 404);
    }

    public function complete(Request $request)
    {
        $this->assertCanViewModule('roles');
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $perPageRaw = (string) $request->query('per_page', '15');

        $perPageOptions = ['10', '15', '30', '50', '100', 'all'];
        $perPage = in_array($perPageRaw, $perPageOptions, true) ? $perPageRaw : '15';

        $query = Role::withCount('users');
        $empresa = $this->empresaActivaParaScope();
        if ($empresa) {
            $prefijo = $this->prefijoEmpresa($empresa);
            $query->where(function ($q) use ($empresa, $prefijo) {
                $q->where('name', 'like', $prefijo.'-%');
                if ($empresa->default_role_id) {
                    $q->orWhere('id', $empresa->default_role_id);
                }
            })->whereRaw('LOWER(name) != ?', ['administrador']);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if ($status === 'active') {
            $query->where('activo', true);
        } elseif ($status === 'inactive') {
            $query->where('activo', false);
        }

        $query->orderBy('id', 'desc');

        // Clonar ANTES de paginar: MySQL no admite LIMIT dentro de subconsultas IN.
        $filteredQuery = clone $query;

        $chartTopUsersByRole = (clone $filteredQuery)
            ->reorder()
            ->orderByDesc('users_count')
            ->limit(10)
            ->get()
            ->map(function ($r) {
                return [
                    'label' => \Illuminate\Support\Str::limit((string) $r->name, 34),
                    'count' => (int) $r->users_count,
                ];
            })
            ->values()
            ->all();

        if ($perPage === 'all') {
            $items = (clone $filteredQuery)->get();
            $roles = new LengthAwarePaginator(
                $items,
                $items->count(),
                $items->count() > 0 ? $items->count() : 1,
                1,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
        } else {
            $roles = (clone $filteredQuery)->paginate((int) $perPage)->appends($request->query());
        }

        $totalRoles = (clone $filteredQuery)->count();
        $roleIds = (clone $filteredQuery)->reorder()->pluck('id');
        $totalUsersAssigned = $roleIds->isEmpty()
            ? 0
            : (int) User::query()->whereIn('role_id', $roleIds)->count();
        $rolesActive = (clone $filteredQuery)->where('activo', true)->count();
        $rolesInactive = (clone $filteredQuery)->where('activo', false)->count();
        $stats = [
            'total' => $totalRoles,
            'users_assigned' => $totalUsersAssigned,
            'avg_users_per_role' => $totalRoles > 0 ? round($totalUsersAssigned / $totalRoles, 1) : 0,
            'roles_active' => $rolesActive,
            'roles_inactive' => $rolesInactive,
        ];

        // Apartados de acceso disponibles según módulos de la empresa activa.
        $availablePermissions = $this->availablePermissionsForEmpresa($empresa);

        $soloRolEmpresa = (bool) $empresa;
        $empresaPrefijo = $empresa ? $this->prefijoEmpresa($empresa) : null;

        return view('admin.roles.complete', compact(
            'roles',
            'stats',
            'q',
            'status',
            'perPage',
            'availablePermissions',
            'soloRolEmpresa',
            'empresaPrefijo',
            'chartTopUsersByRole'
        ));
    }

    public function index()
    {
        $this->assertCanViewModule('roles');
        $query = Role::withCount('users');
        $empresa = $this->empresaActivaParaScope();
        if ($empresa) {
            $prefijo = $this->prefijoEmpresa($empresa);
            $query->where(function ($q) use ($empresa, $prefijo) {
                $q->where('name', 'like', $prefijo.'-%');
                if ($empresa->default_role_id) {
                    $q->orWhere('id', $empresa->default_role_id);
                }
            })->whereRaw('LOWER(name) != ?', ['administrador']);
        }

        $roles = $query->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $this->assertCanEditModule('roles');
        $empresa = $this->empresaActivaParaScope();

        // Apartados de acceso disponibles según módulos de la empresa activa.
        $availablePermissions = $this->availablePermissionsForEmpresa($empresa);
        $soloRolEmpresa = (bool) $empresa;
        $empresaPrefijo = $empresa ? $this->prefijoEmpresa($empresa) : null;

        return view('admin.roles.create', compact('availablePermissions', 'soloRolEmpresa', 'empresaPrefijo'));
    }

    public function store(Request $request)
    {
        $this->assertCanEditModule('roles');
        $empresa = $this->empresaActivaParaScope();
        $name = $this->nombreRolScoped((string) $request->input('name', ''), $empresa);
        $request->merge(['name' => $name]);

        $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        if ($empresa && strtolower(trim((string) $request->input('name'))) === 'administrador') {
            return redirect()->route('roles.complete')->with('error', '❌ El rol administrador es global y no pertenece a una empresa.');
        }

        $allowedPermKeys = array_keys($this->availablePermissionsForEmpresa($empresa));
        $perms = array_values(array_unique(array_filter((array) $request->input('permissions', []), fn ($p) => in_array($p, $allowedPermKeys, true))));

        $role = new Role();
        $role->fill([
            'name' => $request->name,
            'description' => $request->description,
            'activo' => true,
        ]);
        $role->forceFill(['permissions' => $perms]);
        $role->save();

        return redirect()->route('roles.complete')->with('success', '✅ Rol creado exitosamente');
    }

    public function edit(Role $role)
    {
        $this->assertCanViewModule('roles');
        $this->assertRoleInScope($role);
        return response()->json($role->only(['id', 'name', 'description', 'activo', 'permissions']));
    }

    public function update(Request $request, Role $role)
    {
        $this->assertCanEditModule('roles');
        $this->assertRoleInScope($role);
        $empresa = $this->empresaActivaParaScope();
        $name = $this->nombreRolScoped((string) $request->input('name', ''), $empresa);
        $request->merge(['name' => $name]);

        $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role->id)],
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        $allowedPermKeys = array_keys($this->availablePermissionsForEmpresa($empresa));
        $perms = array_values(array_unique(array_filter((array) $request->input('permissions', []), fn ($p) => in_array($p, $allowedPermKeys, true))));

        $role->fill([
            'name' => $request->name,
            'description' => $request->description,
        ]);
        $role->forceFill(['permissions' => $perms]);
        $role->save();

        return redirect()->route('roles.complete')->with('success', '✅ Rol actualizado exitosamente');
    }

    public function toggleStatus(Role $role)
    {
        $this->assertCanEditModule('roles');
        $this->assertRoleInScope($role);
        $role->update(['activo' => ! $role->activo]);
        $status = $role->activo ? 'activado' : 'desactivado';

        return response()->json([
            'success' => true,
            'message' => "✅ Rol {$status} exitosamente.",
        ]);
    }

    public function destroy(Role $role)
    {
        $this->assertCanEditModule('roles');
        $this->assertRoleInScope($role);
        try {
            if ($role->users()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar: hay usuarios asignados a este rol.',
                ], 422);
            }
            $role->delete();
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'No se pudo eliminar el rol.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rol eliminado correctamente.',
        ]);
    }
}
