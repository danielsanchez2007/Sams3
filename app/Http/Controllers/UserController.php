<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Cargo;
use App\Models\Grupo;
use App\Models\Empresa;
use App\Services\EmpresaContext;
use App\Support\UploadedFileStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Carbon\Carbon;

class UserController extends Controller
{
    private function purgeUserRelations(User $user): void
    {
        $id = (int) $user->id;

        // Limpia posibles referencias que bloquean el DELETE por llaves foráneas.
        if (Schema::hasTable('grupo_members') && Schema::hasColumn('grupo_members', 'user_id')) {
            DB::table('grupo_members')->where('user_id', $id)->delete();
        }

        if (Schema::hasTable('grupos') && Schema::hasColumn('grupos', 'leader_id')) {
            DB::table('grupos')->where('leader_id', $id)->update(['leader_id' => null]);
        }

        if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
            DB::table('sessions')->where('user_id', $id)->delete();
        }

        if (Schema::hasColumn('users', 'grupo_id')) {
            DB::table('users')->where('id', $id)->update(['grupo_id' => null]);
        }

        if (Schema::hasTable('empresas') && Schema::hasColumn('empresas', 'default_user_id')) {
            DB::table('empresas')->where('default_user_id', $id)->update(['default_user_id' => null]);
        }

        if (Schema::hasTable('password_reset_tokens') && Schema::hasColumn('password_reset_tokens', 'email')) {
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        }

        // Nullificar FKs opcionales (nullOnDelete / sin cascade)
        $nullableRefs = [
            ['aviso_empresas', 'created_by'],
            ['hoja_vida_documentos', 'creado_por'],
            ['hoja_vida_documentos', 'actualizado_por'],
            ['hoja_vida_documentos', 'signature_user_id'],
            ['hoja_vida_plantillas', 'creado_por'],
            ['equipos_baja', 'creado_por'],
            ['equipos_auditoria_traspasos', 'traspasado_por'],
            ['material_didactico_traspasos', 'traspasado_por'],
            ['historial_mantenimientos', 'tecnico_id'],
            ['equipo_inspecciones', 'user_id'],
            ['equipo_inspecciones', 'inspector_user_id'],
            ['codigos_reutilizables', 'reservado_por'],
            ['equipo_asignacion_solicitudes_items', 'from_user_id'],
            ['prestamos_temporales_items', 'revision_by'],
        ];
        foreach ($nullableRefs as [$table, $column]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                DB::table($table)->where($column, $id)->update([$column => null]);
            }
        }

        // Borrar filas hijas que pueden impedir el DELETE si el cascade no está activo
        $deletableRefs = [
            ['equipo_asignaciones', 'user_id'],
            ['auditoria_equipos', 'auditor_id'],
            ['notifications', 'notifiable_id'],
            ['personal_access_tokens', 'tokenable_id'],
        ];
        foreach ($deletableRefs as [$table, $column]) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                continue;
            }
            $q = DB::table($table)->where($column, $id);
            if ($table === 'notifications' && Schema::hasColumn($table, 'notifiable_type')) {
                $q->where('notifiable_type', User::class);
            }
            if ($table === 'personal_access_tokens' && Schema::hasColumn($table, 'tokenable_type')) {
                $q->where('tokenable_type', User::class);
            }
            $q->delete();
        }

        // Préstamos / solicitudes: borrar items y luego cabecera
        if (Schema::hasTable('prestamos_temporales')) {
            $prestamoIds = [];
            if (Schema::hasColumn('prestamos_temporales', 'created_by')) {
                $prestamoIds = array_merge($prestamoIds, DB::table('prestamos_temporales')->where('created_by', $id)->pluck('id')->all());
            }
            if (Schema::hasColumn('prestamos_temporales', 'to_user_id')) {
                $prestamoIds = array_merge($prestamoIds, DB::table('prestamos_temporales')->where('to_user_id', $id)->pluck('id')->all());
            }
            $prestamoIds = array_values(array_unique(array_map('intval', $prestamoIds)));
            if ($prestamoIds !== [] && Schema::hasTable('prestamos_temporales_items')) {
                DB::table('prestamos_temporales_items')->whereIn('prestamo_id', $prestamoIds)->delete();
            }
            if ($prestamoIds !== []) {
                DB::table('prestamos_temporales')->whereIn('id', $prestamoIds)->delete();
            }
        }

        if (Schema::hasTable('equipo_asignacion_solicitudes')) {
            $solIds = [];
            if (Schema::hasColumn('equipo_asignacion_solicitudes', 'created_by')) {
                $solIds = array_merge($solIds, DB::table('equipo_asignacion_solicitudes')->where('created_by', $id)->pluck('id')->all());
            }
            if (Schema::hasColumn('equipo_asignacion_solicitudes', 'to_user_id')) {
                $solIds = array_merge($solIds, DB::table('equipo_asignacion_solicitudes')->where('to_user_id', $id)->pluck('id')->all());
            }
            $solIds = array_values(array_unique(array_map('intval', $solIds)));
            if ($solIds !== [] && Schema::hasTable('equipo_asignacion_solicitudes_items')) {
                DB::table('equipo_asignacion_solicitudes_items')->whereIn('solicitud_id', $solIds)->delete();
            }
            if ($solIds !== []) {
                DB::table('equipo_asignacion_solicitudes')->whereIn('id', $solIds)->delete();
            }
        }
    }
    private function resolveEmpresaContextId(?User $user = null): ?int
    {
        return EmpresaContext::empresaId() ?: ($user?->empresa_id ?? auth()->user()?->empresa_id);
    }

    private function rolesPermitidosQuery(?int $empresaId)
    {
        $q = Role::query();
        $hasActivo = Schema::hasColumn('roles', 'activo');
        $hasActive = Schema::hasColumn('roles', 'active');
        if ($hasActivo && $hasActive) {
            $q->where(function ($w) {
                $w->where('activo', true)->orWhere('active', true);
            });
        } elseif ($hasActivo) {
            $q->where('activo', true);
        } elseif ($hasActive) {
            $q->where('active', true);
        }

        if (!$empresaId) {
            return $q;
        }

        $prefijo = strtoupper((string) (Empresa::find($empresaId)?->prefijo ?? ''));
        $prefijo = preg_replace('/[^A-Z0-9]/', '', $prefijo) ?: '';
        if ($prefijo === '') {
            // Si no hay prefijo definido, no forzamos recorte agresivo.
            return $q;
        }

        return $q->whereRaw('UPPER(name) LIKE ?', [$prefijo . '-%']);
    }

    private function roleIdPermitidoParaEmpresa(?int $empresaId, ?int $roleId): bool
    {
        if (!$roleId) {
            return false;
        }
        return $this->rolesPermitidosQuery($empresaId)->whereKey($roleId)->exists();
    }

    private function cargosPermitidosQuery(?int $empresaId)
    {
        $q = Cargo::query();
        $hasActivo = Schema::hasColumn('cargos', 'activo');
        $hasActive = Schema::hasColumn('cargos', 'active');
        if ($hasActivo && $hasActive) {
            $q->where(function ($w) {
                $w->where('activo', true)->orWhere('active', true);
            });
        } elseif ($hasActivo) {
            $q->where('activo', true);
        } elseif ($hasActive) {
            $q->where('active', true);
        }

        if (!$empresaId) {
            return $q;
        }

        // Preferimos filtrar por columna empresa_id si existe (multi-tenant real).
        // Si no existe, NO forzamos prefijos en name (rompe catálogos globales como "Administrador", "Técnico", etc.).
        if (Schema::hasColumn('cargos', 'empresa_id')) {
            return $q->where('empresa_id', $empresaId);
        }

        // Si no existe empresa_id, no se puede filtrar de forma segura; se devuelve todo.
        return $q;
    }

    private function gruposPermitidosQuery(?int $empresaId)
    {
        $q = Grupo::query();
        $hasActivo = Schema::hasColumn('grupos', 'activo');
        $hasActive = Schema::hasColumn('grupos', 'active');
        if ($hasActivo && $hasActive) {
            $q->where(function ($w) {
                $w->where('activo', true)->orWhere('active', true);
            });
        } elseif ($hasActivo) {
            $q->where('activo', true);
        } elseif ($hasActive) {
            $q->where('active', true);
        }

        if (!$empresaId) {
            return $q;
        }

        if (Schema::hasColumn('grupos', 'empresa_id')) {
            return $q->where('empresa_id', $empresaId);
        }

        return $q;
    }

    private function isGlobalAdminRole(int $roleId): bool
    {
        $role = Role::find($roleId);
        return strtolower((string) $role?->name) === 'administrador';
    }

    private function defaultRoleForEmpresaId(?int $empresaId): ?int
    {
        if (!$empresaId) {
            return null;
        }
        $empresa = Empresa::find($empresaId);
        return $empresa?->default_role_id;
    }

    private function assertUserInCurrentEmpresa(User $user): void
    {
        $empresaId = EmpresaContext::empresaId();
        if ($empresaId && (int) $user->empresa_id !== (int) $empresaId) {
            abort(404);
        }
    }

    private function nextCodigoForEmpresa(int $empresaId): string
    {
        $prefijo = strtoupper(EmpresaContext::prefijo());
        $empresa = Empresa::find($empresaId);
        $tag = strtoupper((string) (($empresa?->code_settings['user_tag'] ?? 'USR')));
        $tag = preg_replace('/[^A-Z0-9]/', '', $tag) ?: 'USR';
        $count = User::where('empresa_id', $empresaId)->count() + 1;
        return $prefijo . '-' . $tag . '-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    public function complete(Request $request)
    {
        $this->authorize('viewAny', User::class);
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $roleId = (string) $request->query('role_id', 'all');
        $perPageRaw = (string) $request->query('per_page', '15');

        $perPageOptions = ['10', '15', '30', '50', '100', 'all'];
        $perPage = in_array($perPageRaw, $perPageOptions, true) ? $perPageRaw : '15';

        $empresaId = EmpresaContext::empresaId();

        $query = User::with(['role', 'cargo', 'grupo', 'empresa']);

        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($status === 'active') {
            $query->where('active', true);
        } elseif ($status === 'inactive') {
            $query->where('active', false);
        } elseif ($status === 'pending') {
            $query->where('active', false)->whereNull('role_id');
        }

        if ($roleId !== 'all' && ctype_digit($roleId)) {
            $query->where('role_id', (int) $roleId);
        }

        $query->orderBy('id', 'desc');

        if ($perPage === 'all') {
            $items = $query->get();
            $users = new LengthAwarePaginator(
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
            $users = $query->paginate((int) $perPage)->appends($request->query());
        }

        $roles = $this->rolesPermitidosQuery($empresaId)->orderBy('name')->get();
        $cargos = $this->cargosPermitidosQuery($empresaId)->orderBy('name')->get();
        $grupos = $this->gruposPermitidosQuery($empresaId)->orderBy('name')->get();

        $statsBase = User::query();
        if ($empresaId) {
            $statsBase->where('empresa_id', $empresaId);
        }

        $statsRow = (clone $statsBase)->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN active = 0 AND role_id IS NULL THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN email_verified_at IS NOT NULL THEN 1 ELSE 0 END) as verified
            ')->first();

        $stats = [
            'total' => (int) ($statsRow->total ?? 0),
            'active' => (int) ($statsRow->active ?? 0),
            'pending' => (int) ($statsRow->pending ?? 0),
            'verified' => (int) ($statsRow->verified ?? 0),
            'admins' => (clone $statsBase)->whereHas('role', function ($r) {
                $r->where('name', 'administrador');
            })->count(),
        ];

        $canEditUsers = $this->canEditUsersModule();

        return view('admin.users.complete', compact('users', 'roles', 'cargos', 'grupos', 'stats', 'q', 'status', 'roleId', 'perPage', 'canEditUsers'));
    }

    /** Si la empresa tiene módulo users con nivel "view" (solo vista), no puede editar/crear. */
    private function canEditUsersModule(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        $empresa = EmpresaContext::empresaActiva();
        $isGlobalAdmin = !$user->empresa_id;
        $roleName = strtolower(trim((string) ($user->role?->name ?? '')));
        $isEmpresaAdminByRole = $user->empresa_id && (str_contains($roleName, 'admin') || str_contains($roleName, 'administrador'));
        $rolePerms = $user->role?->permissions;
        $isEmpresaAdminByPerm = $user->empresa_id && is_array($rolePerms) && in_array('users', $rolePerms, true);

        if (!$empresa || !$empresa->modulos || !is_array($empresa->modulos)) {
            return $isGlobalAdmin || $isEmpresaAdminByRole || $isEmpresaAdminByPerm;
        }
        $raw = $empresa->modulos;
        $val = $raw['users'] ?? $raw['gestion_principal'] ?? 'none';
        if (!in_array($val, ['none', 'view', 'edit'], true)) {
            $val = 'none';
        }
        $moduloPermiteEdicion = $val === 'edit';

        if ($isGlobalAdmin) {
            return $moduloPermiteEdicion;
        }
        return $moduloPermiteEdicion && ($isEmpresaAdminByRole || $isEmpresaAdminByPerm);
    }

    public function index()
    {
        return redirect()->route('users.complete');
    }

    public function create()
    {
        return redirect()->route('users.complete');
    }

    public function store(Request $request)
    {
        if (!$this->canEditUsersModule()) {
            abort(403);
        }
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);
        $empresaIdContexto = $this->resolveEmpresaContextId();
        $allowedRoleIds = $this->rolesPermitidosQuery($empresaIdContexto)->pluck('id')->all();

        $allowedCargoIds = $this->cargosPermitidosQuery($empresaIdContexto)->pluck('id')->all();
        $allowedGrupoIds = $this->gruposPermitidosQuery($empresaIdContexto)->pluck('id')->all();

        $request->merge([
            'cargo_id' => $request->filled('cargo_id') ? $request->input('cargo_id') : null,
            'grupo_id' => $request->filled('grupo_id') ? $request->input('grupo_id') : null,
            'document_type' => $request->filled('document_type') ? $request->input('document_type') : null,
            'document_number' => $request->filled('document_number') ? trim((string) $request->input('document_number')) : null,
            'birth_date' => $request->filled('birth_date') ? $request->input('birth_date') : null,
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'gender' => 'nullable|in:hombre,mujer,otro',
            'gender_other' => 'nullable|string|max:255|required_if:gender,otro',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', 'max:72', Password::defaults()],
            'document_type' => 'nullable|string|in:CC,CE,TI,PP',
            'document_number' => ['nullable', 'string', 'max:50', Rule::unique('users', 'document_number')],
            'birth_date' => 'nullable|date',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'has_corporate_email' => 'boolean',
            'corporate_email' => 'nullable|email|required_if:has_corporate_email,1',
            'has_corporate_phone' => 'boolean',
            'corporate_phone' => 'nullable|string|max:20|required_if:has_corporate_phone,1',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'signature' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'role_id' => ['required', 'exists:roles,id', Rule::in($allowedRoleIds)],
            'cargo_id' => ['nullable', 'exists:cargos,id', Rule::in($allowedCargoIds)],
            'grupo_id' => ['nullable', 'exists:grupos,id', Rule::in($allowedGrupoIds)],
            'active' => 'boolean'
        ]);

        $hasCorporateEmail = $request->boolean('has_corporate_email', false);
        $hasCorporatePhone = $request->boolean('has_corporate_phone', false);

        $empresaId = EmpresaContext::empresaId();
        if (!$empresaId && $request->filled('role_id')) {
            $role = Role::find((int) $request->input('role_id'));
            $roleName = strtoupper((string) ($role?->name ?? ''));
            if (str_contains($roleName, '-')) {
                $pref = strtok($roleName, '-');
                if ($pref) {
                    $empresaId = Empresa::query()->whereRaw('UPPER(prefijo) = ?', [$pref])->value('id');
                }
            }
        }
        if ($empresaId && $request->filled('role_id') && $this->isGlobalAdminRole((int) $request->input('role_id'))) {
            $fallbackRoleId = $this->defaultRoleForEmpresaId($empresaId);
            if ($fallbackRoleId) {
                $request->merge(['role_id' => $fallbackRoleId]);
            } else {
                return $this->userFormErrorResponse($request, [
                    'role_id' => 'El rol administrador es global y no aplica a usuarios de empresa.',
                ]);
            }
        }

        $user = User::createAccount([
            'codigo' => $empresaId ? $this->nextCodigoForEmpresa($empresaId) : null,
            'name' => $request->name,
            'last_name' => $request->last_name,
            'gender' => $request->gender,
            'gender_other' => $request->gender === 'otro' ? $request->gender_other : null,
            'email' => $request->email,
            'password' => $request->password,
            'document_type' => $request->document_type,
            'document_number' => $request->document_number,
            'birth_date' => $request->birth_date,
            'phone' => $request->phone,
            'address' => $request->address,
            'has_corporate_email' => $hasCorporateEmail,
            'corporate_email' => $hasCorporateEmail ? $request->corporate_email : null,
            'has_corporate_phone' => $hasCorporatePhone,
            'corporate_phone' => $hasCorporatePhone ? $request->corporate_phone : null,
            'role_id' => $request->role_id,
            'cargo_id' => $request->cargo_id,
            'grupo_id' => $request->grupo_id,
            'empresa_id' => $empresaId,
            'active' => $request->boolean('active', true),
            'must_change_password' => true,
        ]);

        if ($request->hasFile('photo')) {
            $path = UploadedFileStorage::storePublicImage($request->file('photo'), 'users/photos');
            $user->update(['photo' => $path]);
        }

        if ($request->hasFile('signature')) {
            $path = UploadedFileStorage::storePublicImage($request->file('signature'), 'users/signatures');
            $user->update(['signature' => $path]);
        }

        $okMessage = 'Usuario creado correctamente.';
        session()->flash('success', $okMessage);
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $okMessage,
                'redirect' => route('users.complete'),
            ]);
        }

        return redirect()->route('users.complete');
    }

    public function edit(User $user)
    {
        if (!$this->canEditUsersModule()) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para editar usuarios.',
                ], 403);
            }
            abort(403);
        }
        $this->assertUserInCurrentEmpresa($user);
        $data = $user->toArray();
        $data['birth_date'] = $user->birth_date ? $user->birth_date->format('Y-m-d') : null;
        return response()->json($data);
    }

    public function update(Request $request, User $user)
    {
        if (!$this->canEditUsersModule()) {
            abort(403);
        }
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);
        $this->assertUserInCurrentEmpresa($user);
        $empresaId = EmpresaContext::empresaId() ?: $user->empresa_id;
        if ($empresaId && $request->filled('role_id') && $this->isGlobalAdminRole((int) $request->input('role_id'))) {
            $fallbackRoleId = $this->defaultRoleForEmpresaId($empresaId);
            if ($fallbackRoleId) {
                $request->merge(['role_id' => $fallbackRoleId]);
            } else {
                return $this->userFormErrorResponse($request, [
                    'role_id' => 'El rol administrador es global y no aplica a usuarios de empresa.',
                ]);
            }
        }
        $empresaIdContexto = $this->resolveEmpresaContextId($user);
        $allowedRoleIds = $this->rolesPermitidosQuery($empresaIdContexto)->pluck('id')->all();

        $allowedCargoIds = $this->cargosPermitidosQuery($empresaIdContexto)->pluck('id')->all();
        $allowedGrupoIds = $this->gruposPermitidosQuery($empresaIdContexto)->pluck('id')->all();

        $request->merge([
            'cargo_id' => $request->filled('cargo_id') ? $request->input('cargo_id') : null,
            'grupo_id' => $request->filled('grupo_id') ? $request->input('grupo_id') : null,
            'document_type' => $request->filled('document_type') ? $request->input('document_type') : null,
            'document_number' => $request->filled('document_number') ? trim((string) $request->input('document_number')) : null,
            'birth_date' => $request->filled('birth_date') ? $request->input('birth_date') : null,
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'gender' => 'nullable|in:hombre,mujer,otro',
            'gender_other' => 'nullable|string|max:255|required_if:gender,otro',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => ['nullable', 'confirmed', 'max:72', Password::defaults()],
            'document_type' => 'nullable|string|in:CC,CE,TI,PP',
            'document_number' => ['nullable', 'string', 'max:50', Rule::unique('users', 'document_number')->ignore($user->id)],
            'birth_date' => 'nullable|date',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'has_corporate_email' => 'boolean',
            'corporate_email' => 'nullable|email|required_if:has_corporate_email,1',
            'has_corporate_phone' => 'boolean',
            'corporate_phone' => 'nullable|string|max:20|required_if:has_corporate_phone,1',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'signature' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'role_id' => ['required', 'exists:roles,id', Rule::in($allowedRoleIds)],
            'cargo_id' => ['nullable', 'exists:cargos,id', Rule::in($allowedCargoIds)],
            'grupo_id' => ['nullable', 'exists:grupos,id', Rule::in($allowedGrupoIds)],
            'active' => 'boolean'
        ]);

        $hasCorporateEmail = $request->boolean('has_corporate_email', false);
        $hasCorporatePhone = $request->boolean('has_corporate_phone', false);

        $user->fillAccount([
            'name' => $request->name,
            'last_name' => $request->last_name,
            'gender' => $request->gender,
            'gender_other' => $request->gender === 'otro' ? $request->gender_other : null,
            'email' => $request->email,
            'document_type' => $request->document_type,
            'document_number' => $request->document_number,
            'birth_date' => $request->birth_date,
            'phone' => $request->phone,
            'address' => $request->address,
            'has_corporate_email' => $hasCorporateEmail,
            'corporate_email' => $hasCorporateEmail ? $request->corporate_email : null,
            'has_corporate_phone' => $hasCorporatePhone,
            'corporate_phone' => $hasCorporatePhone ? $request->corporate_phone : null,
            'role_id' => $request->role_id,
            'cargo_id' => $request->cargo_id,
            'grupo_id' => $request->grupo_id,
            'active' => $request->boolean('active', true)
        ]);
        $user->save();

        // Actualizar contraseña solo si se proporciona (otro usuario => debe redefinirla al entrar; uno mismo => no)
        if ($request->password) {
            $user->fillAccount([
                'password' => $request->password,
                'must_change_password' => (int) $user->id !== (int) auth()->id(),
            ]);
            $user->save();
        }

        if ($request->hasFile('photo')) {
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
            $path = UploadedFileStorage::storePublicImage($request->file('photo'), 'users/photos');
            $user->update(['photo' => $path]);
        }

        if ($request->hasFile('signature')) {
            if ($user->signature) {
                Storage::disk('public')->delete($user->signature);
            }
            $path = UploadedFileStorage::storePublicImage($request->file('signature'), 'users/signatures');
            $user->update(['signature' => $path]);
        }

        $okMessage = 'Usuario actualizado correctamente.';
        session()->flash('success', $okMessage);
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $okMessage,
                'redirect' => route('users.complete'),
            ]);
        }

        return redirect()->route('users.complete');
    }

    public function toggleStatus(Request $request, User $user)
    {
        $wantsJson = $request->expectsJson() || $request->ajax();

        if (!$this->canEditUsersModule()) {
            if ($wantsJson) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para cambiar el estado.',
                ], 403);
            }
            abort(403);
        }
        $this->assertUserInCurrentEmpresa($user);

        if ((int) $user->id === (int) auth()->id() && $user->active) {
            if ($wantsJson) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes desactivar tu propio usuario.',
                ], 422);
            }
            return back()->with('error', 'No puedes desactivar tu propio usuario.');
        }

        $user->forceFill(['active' => ! $user->active])->save();

        $status = $user->active ? 'activado' : 'desactivado';
        return response()->json([
            'success' => true,
            'message' => "Usuario {$status} exitosamente.",
            'active' => (bool) $user->active,
        ]);
    }

    public function show(User $user)
    {
        $this->assertUserInCurrentEmpresa($user);
        return redirect()->route('users.complete');
    }

    public function destroy(User $user)
    {
        $wantsJson = request()->expectsJson() || request()->ajax();

        if (!$this->canEditUsersModule()) {
            if ($wantsJson) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para eliminar usuarios.',
                ], 403);
            }
            abort(403);
        }
        $this->assertUserInCurrentEmpresa($user);
        if ((int) $user->id === (int) auth()->id()) {
            if ($wantsJson) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes eliminar tu propio usuario.',
                ], 422);
            }
            return back()->with('error', 'No puedes eliminar tu propio usuario.');
        }

        try {
            DB::transaction(function () use ($user) {
                $this->purgeUserRelations($user);
                $user->delete();
            });
        } catch (\Throwable $e) {
            Log::error('Error eliminando usuario', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            if ($wantsJson) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo eliminar el usuario porque tiene registros relacionados.',
                ], 422);
            }
            return back()->with('error', 'No se pudo eliminar el usuario porque tiene registros relacionados.');
        }

        if ($wantsJson) {
            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado correctamente.'
            ]);
        }
        return redirect()->route('users.complete')->with('success', 'Usuario eliminado correctamente.');
    }

    public function bulkDestroy(Request $request)
    {
        if (!$this->canEditUsersModule()) {
            abort(403);
        }

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $data['ids'])));
        $ids = array_values(array_filter($ids, fn ($id) => $id !== (int) auth()->id()));

        if ($ids === []) {
            return back()->with('error', 'No puedes eliminar tu propio usuario (ni dejar la selección vacía).');
        }

        $users = User::query()->whereIn('id', $ids)->get();
        foreach ($users as $u) {
            $this->assertUserInCurrentEmpresa($u);
        }

        DB::transaction(function () use ($users) {
            foreach ($users as $u) {
                $this->purgeUserRelations($u);
                $u->delete();
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Usuarios eliminados correctamente.',
                'deleted_count' => count($users),
            ]);
        }

        return back()->with('success', 'Usuarios eliminados correctamente: ' . count($users));
    }

    public function pendingMeta(User $user)
    {
        if (!$this->canEditUsersModule()) {
            abort(403);
        }
        $this->assertUserInCurrentEmpresa($user);

        if ($user->active || $user->role_id) {
            return response()->json([
                'success' => false,
                'message' => 'La solicitud ya fue procesada.',
            ], 422);
        }

        $roles = $this->rolesPermitidosQuery((int) $user->empresa_id)
            ->orderBy('name')
            ->get(['id', 'name', 'description']);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => trim((string) ($user->name . ' ' . ($user->last_name ?? ''))),
                'email' => $user->email,
            ],
            'roles' => $roles,
        ]);
    }

    public function approvePending(Request $request, User $user)
    {
        if (!$this->canEditUsersModule()) {
            abort(403);
        }
        $this->assertUserInCurrentEmpresa($user);

        if ($user->active || $user->role_id) {
            return response()->json([
                'success' => false,
                'message' => 'La solicitud ya fue procesada.',
            ], 422);
        }

        $allowedRoleIds = $this->rolesPermitidosQuery((int) $user->empresa_id)->pluck('id')->all();
        $data = $request->validate([
            'role_id' => ['required', 'exists:roles,id', Rule::in($allowedRoleIds)],
        ]);

        $user->fillAccount([
            'role_id' => (int) $data['role_id'],
            'active' => true,
            'must_change_password' => true,
        ]);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Solicitud aceptada y usuario activado.',
        ]);
    }

    public function rejectPending(Request $request, User $user)
    {
        if (!$this->canEditUsersModule()) {
            abort(403);
        }
        $this->assertUserInCurrentEmpresa($user);

        if ($user->active || $user->role_id) {
            return response()->json([
                'success' => false,
                'message' => 'La solicitud ya fue procesada.',
            ], 422);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        Log::info('Solicitud de acceso rechazada', [
            'user_id' => $user->id,
            'email' => $user->email,
            'empresa_id' => $user->empresa_id,
            'rejected_by' => auth()->id(),
            'reason' => $data['reason'],
        ]);

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Solicitud rechazada y eliminada.',
        ]);
    }

    private function userFormErrorResponse(Request $request, array $errors)
    {
        if ($request->expectsJson() || $request->ajax()) {
            $normalized = [];
            foreach ($errors as $key => $message) {
                $normalized[$key] = array_values((array) $message);
            }

            return response()->json([
                'message' => collect($normalized)->flatten()->first(),
                'errors' => $normalized,
            ], 422);
        }

        return back()->withErrors($errors)->withInput();
    }
}
