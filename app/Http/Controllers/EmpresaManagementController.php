<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use App\Models\Empresa;
use App\Models\Espacio;
use App\Models\Oficina;
use App\Models\Role;
use App\Models\Sede;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Services\EmpresaContext;
use App\Services\EmpresaModuleAuthorization;
use App\Support\UploadedFileStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmpresaManagementController extends Controller
{
    private function authz(): EmpresaModuleAuthorization
    {
        return new EmpresaModuleAuthorization();
    }

    private function ensureGlobalAdmin(): void
    {
        if (auth()->user()?->empresa_id) {
            abort(403, 'Solo el administrador global puede realizar esta acción.');
        }
    }

    /** Salir del contexto de empresa (volver a ver todo como admin principal). */
    public function salirEmpresa()
    {
        $this->ensureGlobalAdmin();
        EmpresaContext::salirEmpresa();
        return redirect()->route('equipos.index')->with('success', 'Contexto restaurado. Ves todos los equipos.');
    }

    /** Cambiar contexto a otra empresa (Entrar a empresa). */
    public function entrarEmpresa(Empresa $empresa)
    {
        $this->ensureGlobalAdmin();
        EmpresaContext::entrarEmpresa($empresa);
        return redirect()->route('equipos.index')->with('success', 'Has entrado a ' . $empresa->nombre);
    }

    public function gestion()
    {
        $empresaId = EmpresaContext::empresaId();
        $userEmpresaId = auth()->user()?->empresa_id;
        $empresaActivaId = $empresaId ?: $userEmpresaId;
        $soloMiEmpresa = (bool) $empresaActivaId;

        $empresasQuery = Empresa::withCount('equipos')->with([
            'sedes' => static function ($q) {
                $q->orderBy('nombre')
                    ->withCount(['bodegas', 'oficinas'])
                    ->with([
                        'bodegas' => static fn ($b) => $b->orderBy('nombre')->withCount('equipos'),
                        'oficinas' => static fn ($o) => $o->orderBy('nombre'),
                    ]);
            },
        ]);
        $sedesQuery = Sede::with([
            'empresa',
            'bodegas' => static fn ($b) => $b->orderBy('nombre')->withCount('equipos'),
            'oficinas' => static fn ($o) => $o->orderBy('nombre'),
        ])->withCount(['equipos', 'bodegas', 'oficinas']);
        $bodegasQuery = Bodega::with(['empresa', 'sede'])->withCount('equipos');

        if ($empresaActivaId) {
            $empresasQuery->where('id', $empresaActivaId);
            $sedesQuery->where('empresa_id', $empresaActivaId);
            $bodegasQuery->where('empresa_id', $empresaActivaId);
        }
        
        $empresas = $empresasQuery->orderMatrizFirst()->orderBy('nombre')->get();
        $sedes = $sedesQuery->orderBy('nombre')->get();
        $bodegas = $bodegasQuery->orderBy('nombre')->get();
        $roles = Role::where('activo', true)->orderBy('name')->get();

        $googleMapsApiKey = config('services.google.maps_api_key');
        $empresaActiva = EmpresaContext::empresaActiva();
        $modulos = $empresaActiva?->modulos ?? [];
        $canEditEmpresa = !$soloMiEmpresa || (isset($modulos['empresa']) && $modulos['empresa'] === 'edit');
        $canEditSede = !$soloMiEmpresa || in_array(($modulos['sede'] ?? $modulos['empresa'] ?? 'edit'), ['edit'], true);
        $canEditBodega = !$soloMiEmpresa || in_array(($modulos['bodega'] ?? $modulos['empresa'] ?? 'edit'), ['edit'], true);

        $mostrarPestanaOficinas = EmpresaContext::esPreventionWorld($empresaActiva);
        $oficinas = collect();
        $canEditOficina = false;
        if ($mostrarPestanaOficinas && $empresaActiva) {
            $oficinas = Oficina::query()
                ->with(['sede', 'empresa'])
                ->where('empresa_id', $empresaActiva->id)
                ->orderBy('nombre')
                ->get();
            $canEditOficina = $canEditSede;
        }

        $espaciosQuery = Espacio::query()->with(['sede', 'empresa']);
        if ($empresaActivaId) {
            $espaciosQuery->where('empresa_id', $empresaActivaId);
        }
        $espacios = $espaciosQuery->orderBy('nombre')->get();
        $canEditEspacio = $canEditSede;

        $oficinasMapQuery = Oficina::query()->with(['empresa', 'sede']);
        if ($empresaActivaId) {
            $oficinasMapQuery->where('empresa_id', $empresaActivaId);
        }
        $oficinasMap = $oficinasMapQuery->orderBy('nombre')->get();

        return view('admin.empresa.gestion', compact(
            'empresas',
            'sedes',
            'bodegas',
            'googleMapsApiKey',
            'roles',
            'soloMiEmpresa',
            'canEditEmpresa',
            'canEditSede',
            'canEditBodega',
            'empresaActivaId',
            'mostrarPestanaOficinas',
            'oficinas',
            'canEditOficina',
            'oficinasMap',
            'espacios',
            'canEditEspacio'
        ));
    }

    public function codigos()
    {
        $this->ensureGlobalAdmin();
        if (!Schema::hasColumn('empresas', 'code_settings')) {
            return redirect()->route('empresa.gestion')->with('error', 'La columna code_settings no existe aún. Ejecuta migraciones.');
        }
        $empresas = Empresa::query()->with(['tiposEquipo' => function ($q) {
            $q->orderBy('nombre')->select(['id', 'empresa_id', 'nombre', 'alias']);
        }])->orderMatrizFirst()->orderBy('nombre')->get();
        return view('admin.empresa.codigos', compact('empresas'));
    }

    public function updateCodigos(Request $request, Empresa $empresa)
    {
        $this->ensureGlobalAdmin();
        if (!Schema::hasColumn('empresas', 'code_settings')) {
            return redirect()->route('empresa.gestion')->with('error', 'La columna code_settings no existe aún. Ejecuta migraciones.');
        }
        $data = $request->validate([
            'prefijo' => 'required|string|max:20',
            'role_tag' => 'nullable|string|max:10',
            'user_tag' => 'nullable|string|max:10',
            'inventory_tag' => 'nullable|string|max:10',
            'baja_tag' => 'nullable|string|max:10',
            'auditoria_tag' => 'nullable|string|max:10',
            'material_tag' => 'nullable|string|max:10',
            'tipo_tag' => 'nullable|string|max:10',
            'tipo_ids' => 'nullable|array',
            'tipo_ids.*' => 'nullable|integer',
            'tipo_nombres' => 'nullable|array',
            'tipo_nombres.*' => 'nullable|string|max:100',
            'tipo_aliases' => 'nullable|array',
            'tipo_aliases.*' => 'nullable|string|max:20',
        ]);

        $normalize = function (?string $v): ?string {
            $v = strtoupper(trim((string) $v));
            $v = preg_replace('/[^A-Z0-9]/', '', $v) ?: '';
            return $v === '' ? null : $v;
        };

        $newPref = strtoupper((string) ($normalize($data['prefijo'] ?? '') ?: 'EMP'));
        $oldPref = strtoupper((string) ($empresa->prefijo ?: ''));
        if (
            Empresa::query()
                ->where('id', '!=', $empresa->id)
                ->whereRaw('UPPER(prefijo) = ?', [$newPref])
                ->exists()
        ) {
            return redirect()->route('empresa.codigos')->with('error', 'El prefijo base ya está en uso por otra empresa.');
        }

        $settings = [
            'role_tag' => $normalize($data['role_tag'] ?? null) ?: 'ROL',
            'user_tag' => $normalize($data['user_tag'] ?? null) ?: 'USR',
            'inventory_tag' => $normalize($data['inventory_tag'] ?? null) ?: 'IN',
            'baja_tag' => $normalize($data['baja_tag'] ?? null) ?: 'DB',
            'auditoria_tag' => $normalize($data['auditoria_tag'] ?? null) ?: 'AUD',
            'material_tag' => $normalize($data['material_tag'] ?? null) ?: 'MD',
            'tipo_tag' => $normalize($data['tipo_tag'] ?? null) ?: 'TIP',
        ];

        DB::transaction(function () use ($empresa, $newPref, $oldPref, $settings, $data, $normalize) {
            $empresa->prefijo = $newPref;
            $empresa->code_settings = $settings;
            $empresa->save();

            if ($oldPref !== '' && $oldPref !== $newPref) {
                Role::query()
                    ->whereRaw('UPPER(name) LIKE ?', [$oldPref . '-%'])
                    ->get()
                    ->each(function (Role $role) use ($oldPref, $newPref) {
                        $role->name = preg_replace('/^' . preg_quote($oldPref, '/') . '-/i', $newPref . '-', (string) $role->name) ?: $role->name;
                        $role->save();
                    });
            }
            // Actualizar tipos de equipos          
            $ids = (array) ($data['tipo_ids'] ?? []);
            $nombres = (array) ($data['tipo_nombres'] ?? []);
            $aliases = (array) ($data['tipo_aliases'] ?? []);
            $rows = max(count($ids), count($nombres), count($aliases));
            for ($i = 0; $i < $rows; $i++) {
                $id = isset($ids[$i]) && $ids[$i] !== '' ? (int) $ids[$i] : null;
                $nombre = trim((string) ($nombres[$i] ?? ''));
                $alias = strtoupper((string) ($normalize($aliases[$i] ?? null) ?: ''));
                if ($nombre === '') {
                    continue;
                }

                if ($id) {
                    $tipo = TipoEquipo::query()->where('empresa_id', $empresa->id)->where('id', $id)->first();
                    if ($tipo) {
                        $tipo->nombre = $nombre;
                        $tipo->alias = $alias !== '' ? $alias : null;
                        $tipo->save();
                    }
                } else {
                    TipoEquipo::query()->firstOrCreate(
                        ['empresa_id' => $empresa->id, 'nombre' => $nombre],
                        ['alias' => $alias !== '' ? $alias : null, 'descripcion' => null, 'activo' => true]
                    );
                }
            }
        });

        return redirect()->route('empresa.codigos')->with('success', '✅ Códigos actualizados para ' . $empresa->nombre . '.');
    }

    public function geocode(Request $request)
    {
        $request->validate([
            'address' => 'required|string|max:600',
        ]);

        $apiKey = config('services.google.maps_api_key');
        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Google Maps API Key no configurada.'
            ], 422);
        }

        $address = trim($request->input('address'));
        $cacheKey = 'geocode:' . sha1(mb_strtolower($address));

        $coords = Cache::remember($cacheKey, now()->addDays(30), function () use ($address, $apiKey) {
            $res = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $address,
                'key' => $apiKey,
            ]);

            if (!$res->ok()) {
                return null;
            }

            $json = $res->json();
            $result = $json['results'][0]['geometry']['location'] ?? null;
            if (!$result || !isset($result['lat'], $result['lng'])) {
                return null;
            }

            return [
                'lat' => $result['lat'],
                'lng' => $result['lng'],
            ];
        });

        if (!$coords) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo obtener coordenadas para la dirección.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $coords,
        ]);
    }

    public function editEmpresa(Empresa $empresa)
    {
        $this->ensureGlobalAdmin();
        $this->authz()->assertCanEditEmpresa((int) $empresa->id);

        return response()->json($empresa->only([
            'id',
            'nombre',
            'modulos',
            'prefijo',
            'color_primario',
            'pais',
            'departamento',
            'municipio',
            'ciudad',
            'direccion',
            'google_maps_url',
            'latitud',
            'longitud',
            'altitud',
            'nit',
            'telefono',
            'email',
            'sitio_web',
            'logo_principal',
            'logo_secundario',
            'foto_empresa',
            'color_secundario_1',
            'color_secundario_2',
            'color_extra_4',
            'color_extra_5',
            'activo'
        ]));
    }

    public function storeEmpresa(Request $request)
    {
        $this->ensureGlobalAdmin();
        $request->validate([
            'nombre' => 'required|string|max:255|unique:empresas,nombre',
            'nit' => 'required|string|max:50|unique:empresas,nit',
            'color_primario' => 'required|string|max:20',
            'color_secundario_1' => 'required|string|max:20',
            'color_secundario_2' => 'required|string|max:20',
            'pais' => 'required|string|max:120',
            'departamento' => 'required|string|max:120',
            'municipio' => 'nullable|string|max:120',
            'ciudad' => 'required|string|max:120',
            'direccion' => 'required|string|max:500',
            'google_maps_url' => 'nullable|url|max:500',
            'latitud' => 'nullable|numeric|required_without:google_maps_url',
            'longitud' => 'nullable|numeric|required_without:google_maps_url',
            'altitud' => 'nullable|numeric',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'sitio_web' => 'nullable|string|max:255',
            'logo_principal_file' => 'nullable|image|max:4096',
            'logo_secundario_file' => 'nullable|image|max:4096',
            'foto_empresa' => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('logo_principal_file') && $request->hasFile('logo_secundario_file')) {
            return back()->withInput()->withErrors(['logo_principal_file' => 'Solo puedes cargar un logo (principal o secundario).']);
        }

        $prefijo = $this->uniqueEmpresaPrefijo($this->buildEmpresaPrefijo($request->nombre));
        $defaultPrincipal = config('sams.default_logos.principal');
        $defaultSecundario = config('sams.default_logos.secundario');

        $logoPrincipal = $defaultPrincipal;
        $logoSecundario = $defaultSecundario;

        try {
            if ($request->hasFile('logo_principal_file')) {
                $logoPrincipal = UploadedFileStorage::storePublicImage($request->file('logo_principal_file'), 'empresas/logos');
            }
            if ($request->hasFile('logo_secundario_file')) {
                $logoSecundario = UploadedFileStorage::storePublicImage($request->file('logo_secundario_file'), 'empresas/logos');
            }

            $fotoEmpresa = $request->hasFile('foto_empresa')
                ? UploadedFileStorage::storePublicImage($request->file('foto_empresa'), 'empresas/fotos')
                : null;
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'logo_principal_file' => 'No se pudo guardar la imagen: ' . $e->getMessage(),
            ]);
        }

        $empresa = Empresa::create([
            'nombre' => $request->nombre,
            'prefijo' => $prefijo,
            'color_primario' => $this->normalizeHex($request->color_primario),
            'color_secundario_1' => $this->normalizeHex($request->color_secundario_1),
            'color_secundario_2' => $this->normalizeHex($request->color_secundario_2),
            'color_extra_4' => '#FFFFFF',
            'color_extra_5' => '#000000',
            'pais' => $request->pais,
            'departamento' => $request->departamento,
            'municipio' => $request->municipio,
            'ciudad' => $request->ciudad,
            'direccion' => $request->direccion,
            'google_maps_url' => $request->google_maps_url,
            'latitud' => $request->latitud,
            'longitud' => $request->longitud,
            'altitud' => $request->altitud,
            'nit' => $request->nit,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'sitio_web' => $request->sitio_web,
            'logo' => $logoPrincipal ?: $logoSecundario,
            'logo_principal' => $logoPrincipal,
            'logo_secundario' => $logoSecundario,
            'foto_empresa' => $fotoEmpresa,
            'modulos' => $this->buildDefaultModules(),
            'activo' => true
        ]);

        return redirect()->route('empresa.gestion')
            ->with('success', '✅ Empresa creada exitosamente. Ahora crea al menos un usuario para esta empresa.')
            ->with('empresa_nueva_id', $empresa->id)
            ->with('empresa_nueva_nombre', $empresa->nombre);
    }

    public function updateEmpresa(Request $request, Empresa $empresa)
    {
        $this->ensureGlobalAdmin();
        $request->validate([
            'nombre' => 'required|string|max:255|unique:empresas,nombre,' . $empresa->id,
            'nit' => 'required|string|max:50|unique:empresas,nit,' . $empresa->id,
            'color_primario' => 'required|string|max:20',
            'color_secundario_1' => 'required|string|max:20',
            'color_secundario_2' => 'required|string|max:20',
            'pais' => 'required|string|max:120',
            'departamento' => 'required|string|max:120',
            'municipio' => 'nullable|string|max:120',
            'ciudad' => 'required|string|max:120',
            'direccion' => 'required|string|max:500',
            'google_maps_url' => 'nullable|url|max:500',
            'latitud' => 'nullable|numeric|required_without:google_maps_url',
            'longitud' => 'nullable|numeric|required_without:google_maps_url',
            'altitud' => 'nullable|numeric',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'sitio_web' => 'nullable|string|max:255',
            'logo_principal_file' => 'nullable|image|max:4096',
            'logo_secundario_file' => 'nullable|image|max:4096',
            'foto_empresa' => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('logo_principal_file') && $request->hasFile('logo_secundario_file')) {
            return back()->withInput()->withErrors(['logo_principal_file' => 'Solo puedes cargar un logo (principal o secundario).']);
        }

        $prefijo = $this->uniqueEmpresaPrefijo($this->buildEmpresaPrefijo($request->nombre), $empresa->id);

        $logoPrincipal = $empresa->logo_principal ?: config('sams.default_logos.principal');
        $logoSecundario = $empresa->logo_secundario ?: config('sams.default_logos.secundario');
        try {
            if ($request->hasFile('logo_principal_file')) {
                $logoPrincipal = UploadedFileStorage::storePublicImage($request->file('logo_principal_file'), 'empresas/logos');
            }
            if ($request->hasFile('logo_secundario_file')) {
                $logoSecundario = UploadedFileStorage::storePublicImage($request->file('logo_secundario_file'), 'empresas/logos');
            }
            $fotoEmpresa = $empresa->foto_empresa;
            if ($request->hasFile('foto_empresa')) {
                $fotoEmpresa = UploadedFileStorage::storePublicImage($request->file('foto_empresa'), 'empresas/fotos');
            }
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'logo_principal_file' => 'No se pudo guardar la imagen: ' . $e->getMessage(),
            ]);
        }

        $empresa->update([
            'nombre' => $request->nombre,
            'prefijo' => $prefijo,
            'color_primario' => $this->normalizeHex($request->color_primario),
            'color_secundario_1' => $this->normalizeHex($request->color_secundario_1),
            'color_secundario_2' => $this->normalizeHex($request->color_secundario_2),
            'color_extra_4' => '#FFFFFF',
            'color_extra_5' => '#000000',
            'pais' => $request->pais,
            'departamento' => $request->departamento,
            'municipio' => $request->municipio,
            'ciudad' => $request->ciudad,
            'direccion' => $request->direccion,
            'google_maps_url' => $request->google_maps_url,
            'latitud' => $request->latitud,
            'longitud' => $request->longitud,
            'altitud' => $request->altitud,
            'nit' => $request->nit,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'sitio_web' => $request->sitio_web,
            'logo' => $logoPrincipal ?: $logoSecundario,
            'logo_principal' => $logoPrincipal,
            'logo_secundario' => $logoSecundario,
            'foto_empresa' => $fotoEmpresa,
        ]);

        return redirect()->route('empresa.gestion')->with('success', '✅ Empresa actualizada exitosamente');
    }

    /** Actualiza los módulos habilitados para una empresa. */
    public function updateEmpresaModulos(Request $request, Empresa $empresa)
    {
        $this->ensureGlobalAdmin();
        $validModules = config('sams.modulos', []);
        $validLevels = config('sams.modulo_niveles', ['none', 'view', 'edit']);

        $data = $request->validate([
            'modulos' => ['nullable', 'array'],
        ]);

        $incoming = $data['modulos'] ?? [];
        $normalized = [];

        foreach ($validModules as $key) {
            $level = $incoming[$key] ?? null;
            if (!in_array($level, $validLevels, true)) {
                // si no se envía o es inválido, por defecto sin acceso explícito (se puede asumir edit cuando no haya configuración)
                continue;
            }
            $normalized[$key] = $level;
        }

        $empresa->modulos = $normalized ?: null;
        $empresa->save();

        return redirect()->route('empresa.gestion')->with('success', '✅ Módulos actualizados para la empresa.');
    }

    /** Dar acceso a todos los módulos (edit) para la empresa. */
    public function updateEmpresaModulosTodo(Empresa $empresa)
    {
        $this->ensureGlobalAdmin();
        $validModules = config('sams.modulos', []);
        $normalized = [];
        foreach ($validModules as $key) {
            $normalized[$key] = 'edit';
        }
        $empresa->modulos = $normalized;
        $empresa->save();

        return redirect()->route('empresa.gestion')->with('success', '✅ Se dio acceso a todos los módulos para ' . $empresa->nombre . '.');
    }

    /** Crear usuario asignado a la empresa (solo verá los apartados configurados para la empresa; código = prefijo + nombre). */
    public function storeEmpresaUser(Request $request, Empresa $empresa)
    {
        $this->ensureGlobalAdmin();
        $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $prefijo = $empresa->prefijo
            ? strtoupper(preg_replace('/[^A-Z0-9]/', '', $empresa->prefijo))
            : strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $empresa->nombre), 0, 3));
        if ($prefijo === '') {
            $prefijo = 'EMP';
        }

        $codigo = $this->codigoUsuarioParaEmpresa($empresa, $prefijo, $request->name, $request->last_name);

        // Rol exclusivo de esta empresa (nombre de rol = nombre de la empresa)
        $role = Role::firstOrCreate(
            ['name' => $empresa->nombre],
            [
                'description' => 'Rol principal de la empresa ' . $empresa->nombre,
                'activo' => true,
            ]
        );

        // Guardar como rol por defecto de la empresa si aún no está definido
        if (!$empresa->default_role_id) {
            $empresa->default_role_id = $role->id;
            $empresa->save();
        }

        User::create([
            'codigo' => $codigo,
            'name' => $request->name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $role->id,
            'empresa_id' => $empresa->id,
            'active' => true,
        ]);

        return redirect()->route('empresa.gestion')->with('success', '✅ Usuario creado con código ' . $codigo . '. Solo tendrá acceso a los apartados configurados para la empresa.');
    }

    /** Genera código único: iniciales empresa + nombre del usuario (ej: APW-JUAN-PEREZ). */
    private function codigoUsuarioParaEmpresa(Empresa $empresa, string $prefijo, string $name, ?string $lastName): string
    {
        $nombreCompleto = trim($name . ' ' . ($lastName ?? ''));
        $slug = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '-', $nombreCompleto));
        $slug = trim(preg_replace('/-+/', '-', $slug), '-');
        if ($slug === '') {
            $slug = 'USU';
        }
        $base = $prefijo . '-' . $slug;
        $codigo = $base;
        $sufijo = 1;
        while (User::where('empresa_id', $empresa->id)->where('codigo', $codigo)->exists()) {
            $codigo = $base . '-' . $sufijo;
            $sufijo++;
        }
        return $codigo;
    }

    private function buildEmpresaPrefijo(string $nombre): string
    {
        $clean = preg_replace('/[^A-Za-z0-9\s]/', ' ', $nombre) ?? '';
        $parts = preg_split('/\s+/', trim($clean)) ?: [];
        $parts = array_values(array_filter($parts, fn ($p) => $p !== ''));
        if (empty($parts)) {
            return 'EMP';
        }

        $maxWords = count($parts) > 3 ? 3 : count($parts);
        $initials = '';
        for ($i = 0; $i < $maxWords; $i++) {
            $initials .= strtoupper(substr($parts[$i], 0, 1));
        }

        $lastWord = (string) end($parts);
        $lastChar = strtoupper(substr($lastWord, -1));
        $base = strtoupper(preg_replace('/[^A-Z0-9]/', '', $initials . $lastChar));

        return $base !== '' ? $base : 'EMP';
    }

    private function uniqueEmpresaPrefijo(string $base, ?int $ignoreEmpresaId = null): string
    {
        $codigo = $base;
        $suffix = strtoupper(substr($base, -1)) ?: 'X';

        while (
            Empresa::query()
                ->when($ignoreEmpresaId, fn ($q) => $q->where('id', '!=', $ignoreEmpresaId))
                ->whereRaw('UPPER(prefijo) = ?', [$codigo])
                ->exists()
        ) {
            $codigo .= $suffix;
            if (strlen($codigo) > 20) {
                $codigo = substr($codigo, 0, 20);
                break;
            }
        }

        return $codigo;
    }

    public function destroyEmpresa(Empresa $empresa)
    {
        $this->ensureGlobalAdmin();

        try {
            $empresa->delete();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la empresa porque tiene sedes, usuarios o equipos asociados. Desactívela en su lugar.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => '🗑️ Empresa eliminada exitosamente.'
        ]);
    }

    public function toggleEmpresaStatus(Empresa $empresa)
    {
        $this->ensureGlobalAdmin();
        $empresa->update(['activo' => !$empresa->activo]);

        $status = $empresa->activo ? 'activada' : 'desactivada';
        return response()->json([
            'success' => true,
            'message' => "✅ Empresa {$status} exitosamente."
        ]);
    }

    public function sedesByEmpresa(Empresa $empresa)
    {
        $this->authz()->assertTenantOwns((int) $empresa->id);

        $sedes = Sede::where('empresa_id', $empresa->id)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return response()->json($sedes);
    }

    public function bodegasByEmpresa(Empresa $empresa)
    {
        $this->authz()->assertTenantOwns((int) $empresa->id);

        $bodegas = Bodega::where('empresa_id', $empresa->id)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'sede_id']);

        return response()->json($bodegas);
    }

    /**
     * Bodegas de la sede, incluyendo las de la empresa que no tienen sede asignada
     * (bodegas generales), para que no queden inaccesibles al registrar equipos.
     */
    public function bodegasBySede(Sede $sede)
    {
        $this->authz()->assertTenantOwns((int) $sede->empresa_id);

        $bodegas = Bodega::query()
            ->where(function ($q) use ($sede) {
                $q->where('sede_id', $sede->id)
                    ->orWhere(function ($q2) use ($sede) {
                        $q2->whereNull('sede_id')->where('empresa_id', $sede->empresa_id);
                    });
            })
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'empresa_id', 'sede_id']);

        return response()->json($bodegas);
    }

    public function oficinasBySede(Sede $sede)
    {
        $this->authz()->assertTenantOwns((int) $sede->empresa_id);

        $oficinas = Oficina::where('sede_id', $sede->id)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'empresa_id']);

        return response()->json($oficinas);
    }

    public function espaciosBySede(Sede $sede)
    {
        $this->authz()->assertTenantOwns((int) $sede->empresa_id);

        $espacios = Espacio::where('sede_id', $sede->id)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'empresa_id']);

        return response()->json($espacios);
    }

    public function editSede(Sede $sede)
    {
        $this->authz()->assertCanEditSede((int) $sede->empresa_id);

        return response()->json($sede->only([
            'id',
            'empresa_id',
            'nombre',
            'pais',
            'departamento',
            'municipio',
            'ciudad',
            'direccion',
            'google_maps_url',
            'activo'
        ]));
    }

    private function buildDefaultModules(): array
    {
        return config('sams.default_modulos', []);
    }

    private function normalizeHex(?string $value): ?string
    {
        $v = strtoupper(trim((string) $value));
        if ($v === '') {
            return null;
        }
        if ($v[0] !== '#') {
            $v = '#' . $v;
        }
        if (preg_match('/^#([A-F0-9]{3})$/', $v)) {
            return '#' . $v[1] . $v[1] . $v[2] . $v[2] . $v[3] . $v[3];
        }
        if (preg_match('/^#([A-F0-9]{6})$/', $v)) {
            return $v;
        }
        return null;
    }

    public function storeSede(Request $request)
    {
        $empresaId = EmpresaContext::resolveId();
        $rules = [
            'empresa_id' => ['required', 'exists:empresas,id'],
            'nombre' => 'required|string|max:255',
            'pais' => 'nullable|string|max:120',
            'departamento' => 'nullable|string|max:120',
            'municipio' => 'nullable|string|max:120',
            'ciudad' => 'nullable|string|max:120',
            'direccion' => 'nullable|string|max:500',
            'google_maps_url' => 'nullable|string|max:500',
        ];
        if ($empresaId) {
            $rules['empresa_id'][] = Rule::in([$empresaId]);
        }
        $request->validate($rules);

        $this->authz()->assertCanEditSede((int) $request->input('empresa_id'));

        Sede::create([
            'empresa_id' => $request->empresa_id,
            'nombre' => $request->nombre,
            'pais' => $request->pais,
            'departamento' => $request->departamento,
            'municipio' => $request->municipio,
            'ciudad' => $request->ciudad,
            'direccion' => $request->direccion,
            'google_maps_url' => $request->google_maps_url,
            'activo' => true
        ]);

        return redirect()->route('empresa.gestion')->with('success', '✅ Sede creada exitosamente');
    }

    public function updateSede(Request $request, Sede $sede)
    {
        $this->authz()->assertCanEditSede((int) $sede->empresa_id);

        $empresaId = EmpresaContext::resolveId();
        $rules = [
            'empresa_id' => ['required', 'exists:empresas,id'],
            'nombre' => 'required|string|max:255',
            'pais' => 'nullable|string|max:120',
            'departamento' => 'nullable|string|max:120',
            'municipio' => 'nullable|string|max:120',
            'ciudad' => 'nullable|string|max:120',
            'direccion' => 'nullable|string|max:500',
            'google_maps_url' => 'nullable|string|max:500',
        ];
        if ($empresaId) {
            $rules['empresa_id'][] = Rule::in([$empresaId]);
        }
        $request->validate($rules);

        $sede->update([
            'empresa_id' => $request->empresa_id,
            'nombre' => $request->nombre,
            'pais' => $request->pais,
            'departamento' => $request->departamento,
            'municipio' => $request->municipio,
            'ciudad' => $request->ciudad,
            'direccion' => $request->direccion,
            'google_maps_url' => $request->google_maps_url
        ]);

        return redirect()->route('empresa.gestion')->with('success', '✅ Sede actualizada exitosamente');
    }

    public function destroySede(Sede $sede)
    {
        $this->authz()->assertCanEditSede((int) $sede->empresa_id);

        try {
            $sede->delete();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la sede porque tiene bodegas, oficinas, espacios o equipos asociados. Elimínelos primero o desactive la sede.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => '🗑️ Sede eliminada exitosamente.'
        ]);
    }

    public function toggleSedeStatus(Sede $sede)
    {
        $this->authz()->assertCanEditSede((int) $sede->empresa_id);

        $sede->update(['activo' => !$sede->activo]);

        $status = $sede->activo ? 'activada' : 'desactivada';
        return response()->json([
            'success' => true,
            'message' => "✅ Sede {$status} exitosamente."
        ]);
    }

    public function editBodega(Bodega $bodega)
    {
        $this->authz()->assertCanEditBodega((int) $bodega->empresa_id);

        return response()->json($bodega->only([
            'id',
            'empresa_id',
            'sede_id',
            'nombre',
            'pais',
            'departamento',
            'municipio',
            'ciudad',
            'direccion',
            'google_maps_url',
            'activo'
        ]));
    }

    public function storeBodega(Request $request)
    {
        $empresaId = EmpresaContext::resolveId();
        $rules = [
            'empresa_id' => ['required', 'exists:empresas,id'],
            'sede_id' => 'nullable|exists:sedes,id',
            'nombre' => 'required|string|max:255',
            'pais' => 'nullable|string|max:120',
            'departamento' => 'nullable|string|max:120',
            'municipio' => 'nullable|string|max:120',
            'ciudad' => 'nullable|string|max:120',
            'direccion' => 'nullable|string|max:500',
            'google_maps_url' => 'nullable|string|max:500',
        ];
        if ($empresaId) {
            $rules['empresa_id'][] = Rule::in([$empresaId]);
        }
        $request->validate($rules);

        $this->authz()->assertCanEditBodega((int) $request->input('empresa_id'));

        Bodega::create([
            'empresa_id' => $request->empresa_id,
            'sede_id' => $request->sede_id,
            'nombre' => $request->nombre,
            'pais' => $request->pais,
            'departamento' => $request->departamento,
            'municipio' => $request->municipio,
            'ciudad' => $request->ciudad,
            'direccion' => $request->direccion,
            'google_maps_url' => $request->google_maps_url,
            'activo' => true
        ]);

        return redirect()->route('empresa.gestion')->with('success', '✅ Bodega creada exitosamente');
    }

    public function updateBodega(Request $request, Bodega $bodega)
    {
        $this->authz()->assertCanEditBodega((int) $bodega->empresa_id);

        $empresaId = EmpresaContext::resolveId();
        $rules = [
            'empresa_id' => ['required', 'exists:empresas,id'],
            'sede_id' => 'nullable|exists:sedes,id',
            'nombre' => 'required|string|max:255',
            'pais' => 'nullable|string|max:120',
            'departamento' => 'nullable|string|max:120',
            'municipio' => 'nullable|string|max:120',
            'ciudad' => 'nullable|string|max:120',
            'direccion' => 'nullable|string|max:500',
            'google_maps_url' => 'nullable|string|max:500',
        ];
        if ($empresaId) {
            $rules['empresa_id'][] = Rule::in([$empresaId]);
        }
        $request->validate($rules);

        $bodega->update([
            'empresa_id' => $request->empresa_id,
            'sede_id' => $request->sede_id,
            'nombre' => $request->nombre,
            'pais' => $request->pais,
            'departamento' => $request->departamento,
            'municipio' => $request->municipio,
            'ciudad' => $request->ciudad,
            'direccion' => $request->direccion,
            'google_maps_url' => $request->google_maps_url
        ]);

        return redirect()->route('empresa.gestion')->with('success', '✅ Bodega actualizada exitosamente');
    }

    public function destroyBodega(Bodega $bodega)
    {
        $this->authz()->assertCanEditBodega((int) $bodega->empresa_id);

        try {
            $bodega->delete();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la bodega porque tiene equipos asociados. Reubíquelos primero o desactive la bodega.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => '🗑️ Bodega eliminada exitosamente.'
        ]);
    }

    public function toggleBodegaStatus(Bodega $bodega)
    {
        $this->authz()->assertCanEditBodega((int) $bodega->empresa_id);

        $bodega->update(['activo' => !$bodega->activo]);

        $status = $bodega->activo ? 'activada' : 'desactivada';
        return response()->json([
            'success' => true,
            'message' => "✅ Bodega {$status} exitosamente."
        ]);
    }

    private function preventionWorldEmpresaActivaOAbort(): Empresa
    {
        $ea = EmpresaContext::empresaActiva();
        abort_unless($ea && EmpresaContext::esPreventionWorld($ea), 403);

        return $ea;
    }

    private function assertOficinaPertenecePreventionWorld(Oficina $oficina, Empresa $pw): void
    {
        abort_unless((int) $oficina->empresa_id === (int) $pw->id, 403);
    }

    public function storeOficina(Request $request)
    {
        $pw = $this->preventionWorldEmpresaActivaOAbort();

        $empresaId = EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;
        $rules = [
            'empresa_id' => ['required', 'exists:empresas,id'],
            'sede_id' => ['required', 'exists:sedes,id'],
            'nombre' => [
                'required',
                'string',
                'max:120',
                Rule::unique('oficinas', 'nombre')->where('sede_id', (int) $request->input('sede_id')),
            ],
        ];
        if ($empresaId) {
            $rules['empresa_id'][] = Rule::in([(int) $empresaId]);
        }
        $data = $request->validate($rules);

        abort_unless((int) $data['empresa_id'] === (int) $pw->id, 403);

        $sede = Sede::query()->whereKey($data['sede_id'])->firstOrFail();
        abort_unless((int) $sede->empresa_id === (int) $pw->id, 422, 'La sede no pertenece a Prevention World.');

        Oficina::create([
            'empresa_id' => $data['empresa_id'],
            'sede_id' => $data['sede_id'],
            'nombre' => $data['nombre'],
        ]);

        return redirect()->route('empresa.gestion')->with('success', '✅ Oficina registrada.');
    }

    public function editOficina(Oficina $oficina)
    {
        $pw = $this->preventionWorldEmpresaActivaOAbort();
        $this->assertOficinaPertenecePreventionWorld($oficina, $pw);

        return response()->json($oficina->only(['id', 'empresa_id', 'sede_id', 'nombre']));
    }

    public function updateOficina(Request $request, Oficina $oficina)
    {
        $pw = $this->preventionWorldEmpresaActivaOAbort();
        $this->assertOficinaPertenecePreventionWorld($oficina, $pw);

        $empresaId = EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;
        $rules = [
            'empresa_id' => ['required', 'exists:empresas,id'],
            'sede_id' => ['required', 'exists:sedes,id'],
            'nombre' => [
                'required',
                'string',
                'max:120',
                Rule::unique('oficinas', 'nombre')
                    ->where('sede_id', (int) $request->input('sede_id'))
                    ->ignore($oficina->id),
            ],
        ];
        if ($empresaId) {
            $rules['empresa_id'][] = Rule::in([(int) $empresaId]);
        }
        $data = $request->validate($rules);

        abort_unless((int) $data['empresa_id'] === (int) $pw->id, 403);

        $sede = Sede::query()->whereKey($data['sede_id'])->firstOrFail();
        abort_unless((int) $sede->empresa_id === (int) $pw->id, 422, 'La sede no pertenece a Prevention World.');

        $oficina->update([
            'empresa_id' => $data['empresa_id'],
            'sede_id' => $data['sede_id'],
            'nombre' => $data['nombre'],
        ]);

        return redirect()->route('empresa.gestion')->with('success', '✅ Oficina actualizada.');
    }

    public function destroyOficina(Oficina $oficina)
    {
        $pw = $this->preventionWorldEmpresaActivaOAbort();
        $this->assertOficinaPertenecePreventionWorld($oficina, $pw);

        try {
            $oficina->delete();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la oficina porque tiene equipos asociados. Reubíquelos primero.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => '🗑️ Oficina eliminada.',
        ]);
    }

    public function storeEspacio(Request $request)
    {
        $empresaId = EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;
        $rules = [
            'empresa_id' => ['required', 'exists:empresas,id'],
            'sede_id' => ['required', 'exists:sedes,id'],
            'nombre' => [
                'required',
                'string',
                'max:120',
                Rule::unique('espacios', 'nombre')->where('sede_id', (int) $request->input('sede_id')),
            ],
        ];
        if ($empresaId) {
            $rules['empresa_id'][] = Rule::in([(int) $empresaId]);
        }
        $data = $request->validate($rules);

        $sede = Sede::query()->whereKey($data['sede_id'])->firstOrFail();
        abort_unless((int) $sede->empresa_id === (int) $data['empresa_id'], 422, 'La sede no pertenece a la empresa seleccionada.');

        Espacio::create([
            'empresa_id' => (int) $data['empresa_id'],
            'sede_id' => (int) $data['sede_id'],
            'nombre' => (string) $data['nombre'],
        ]);

        return redirect()->route('empresa.gestion')->with('success', '✅ Espacio registrado.');
    }

    public function editEspacio(Espacio $espacio)
    {
        $empresaId = EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;
        if ($empresaId) {
            abort_unless((int) $espacio->empresa_id === (int) $empresaId, 403);
        }

        return response()->json($espacio->only(['id', 'empresa_id', 'sede_id', 'nombre']));
    }

    public function updateEspacio(Request $request, Espacio $espacio)
    {
        $empresaId = EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;
        $rules = [
            'empresa_id' => ['required', 'exists:empresas,id'],
            'sede_id' => ['required', 'exists:sedes,id'],
            'nombre' => [
                'required',
                'string',
                'max:120',
                Rule::unique('espacios', 'nombre')
                    ->where('sede_id', (int) $request->input('sede_id'))
                    ->ignore($espacio->id),
            ],
        ];
        if ($empresaId) {
            $rules['empresa_id'][] = Rule::in([(int) $empresaId]);
        }
        $data = $request->validate($rules);

        $sede = Sede::query()->whereKey($data['sede_id'])->firstOrFail();
        abort_unless((int) $sede->empresa_id === (int) $data['empresa_id'], 422, 'La sede no pertenece a la empresa seleccionada.');

        $espacio->update([
            'empresa_id' => (int) $data['empresa_id'],
            'sede_id' => (int) $data['sede_id'],
            'nombre' => (string) $data['nombre'],
        ]);

        return redirect()->route('empresa.gestion')->with('success', '✅ Espacio actualizado.');
    }

    public function destroyEspacio(Espacio $espacio)
    {
        $empresaId = EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;
        if ($empresaId) {
            abort_unless((int) $espacio->empresa_id === (int) $empresaId, 403);
        }

        try {
            $espacio->delete();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el espacio porque tiene equipos asociados. Reubíquelos primero.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => '🗑️ Espacio eliminado.',
        ]);
    }
}