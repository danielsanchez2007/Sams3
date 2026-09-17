<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use App\Services\EmpresaContext;
use App\Models\ClaseEquipo;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\EquipoArchivo;
use App\Models\EquipoImagen;
use App\Models\EquipoKitItem;
use App\Models\Espacio;
use App\Models\Fabricante;
use App\Models\CodigoReutilizable;
use App\Models\Oficina;
use App\Models\Sede;
use App\Models\TipoEquipo;
use App\Http\Requests\StoreEquipoRequest;
use App\Http\Requests\UpdateEquipoRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Support\SensitiveDocumentStorage;
use App\Support\UploadedFileStorage;
use Illuminate\Support\Facades\Storage;

class EquipoInventarioController extends Controller
{
    private function empresaActivaId(): int
    {
        $empresaId = EmpresaContext::empresaId() ?? auth()->user()?->empresa_id;
        if (!$empresaId) {
            if (request()->expectsJson()) {
                abort(403, 'No hay una empresa activa.');
            }
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                redirect()
                    ->route('empresa.gestion')
                    ->with('error', 'Selecciona una empresa activa para continuar.')
            );
        }

        return (int) $empresaId;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Equipo::class);

        $releaseKey = 'sams_release_reserved_codigos';
        if (! Cache::has($releaseKey)) {
            CodigoReutilizable::query()
                ->where('estado', 'reservado')
                ->update([
                    'estado' => 'disponible',
                    'reservado_por' => null,
                    'reservado_en' => null,
                ]);
            Cache::put($releaseKey, 1, 60);
        }

        $syncTtl = (int) config('sams.codigos_sync_ttl', 600);
        $empresaCtx = EmpresaContext::empresaId();
        if ($syncTtl > 0) {
            $syncKey = 'sams_sync_codigos_' . ($empresaCtx ?? 0);
            if (! Cache::has($syncKey)) {
                $this->syncCodigosFaltantes();
                $this->syncCodigosFaltantesParaPrefijo($empresaCtx);
                Cache::put($syncKey, 1, $syncTtl);
            }
        } else {
            $this->syncCodigosFaltantes();
            $this->syncCodigosFaltantesParaPrefijo($empresaCtx);
        }

        $perPageParam = $request->query('per_page', 15);
        $allowedPerPage = [10, 15, 30, 50, 100];
        if ($perPageParam === 'all') {
            $perPage = 500;
        } else {
            $perPage = (int) $perPageParam;
            if (!in_array($perPage, $allowedPerPage, true)) {
                $perPage = 15;
            }
        }

        $q = trim((string) $request->query('q', ''));

        $empresaId = EmpresaContext::empresaId();
        $userEmpresaId = auth()->user()?->empresa_id;

        // Si no hay empresa activa ni usuario ligado a empresa, redirigir al dashboard (no debe ver equipos).
        if (!$empresaId && !$userEmpresaId) {
            return redirect()->route('admin.dashboard');
        }

        // Inventario: códigos con segmento de tipo (ej. PWSS-TEC-IN-0001) o legado (PWSS-IN-0001, IN-0001).
        $patronCodigo = $empresaId ? EmpresaContext::prefijo() . '%-IN-%' : 'IN-%';
        $empresaPrincipalId = Empresa::min('id');
        $esEmpresaPrincipal = $empresaPrincipalId && $empresaId == $empresaPrincipalId;

        $equipos = Equipo::query()
            ->sinTipoPapeleria()
            ->with([
                'tipoEquipo:id,nombre,alias',
                'claseEquipo:id,nombre,alias,tipo_equipo_id',
                'empresa:id,nombre',
                'sede:id,nombre',
                'bodega:id,nombre',
                'fabricante:id,name',
                'imagenes:id,equipo_id,tipo,path',
                'asignacion:id,equipo_id,user_id',
                'prestamoTemporalItems:id,prestamo_id,equipo_id',
                'prestamoTemporalItems.prestamo:id,estado',
            ])
            ->when($empresaId, function ($q) use ($empresaId, $esEmpresaPrincipal) {
                if ($esEmpresaPrincipal) {
                    $q->where(fn ($sub) => $sub->where('empresa_id', $empresaId)->orWhereNull('empresa_id'));
                } else {
                    $q->where('empresa_id', $empresaId);
                }
            })
            ->where('activo', true)
            ->whereNotNull('codigo')
            ->where(function ($q) use ($patronCodigo, $empresaId, $esEmpresaPrincipal) {
                $q->where('codigo', 'like', $patronCodigo);
                if (!$empresaId || $esEmpresaPrincipal) {
                    $q->orWhere('codigo', 'like', 'IN-%');
                }
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('nombre', 'like', "%{$q}%")
                        ->orWhere('serial', 'like', "%{$q}%")
                        ->orWhere('codigo', 'like', "%{$q}%")
                        ->orWhere('lote', 'like', "%{$q}%")
                        ->orWhere('numero_factura', 'like', "%{$q}%");
                });
            })
            ->when($request->filled('tipo_equipo_id'), fn ($query) => $query->where('tipo_equipo_id', $request->integer('tipo_equipo_id')))
            ->when($request->filled('clase_equipo_id'), fn ($query) => $query->where('clase_equipo_id', $request->integer('clase_equipo_id')))
            ->when(!$empresaId && $request->filled('empresa_id'), fn ($query) => $query->where('empresa_id', $request->integer('empresa_id')))
            ->when($request->filled('sede_id'), fn ($query) => $query->where('sede_id', $request->integer('sede_id')))
            ->when($request->filled('bodega_id'), fn ($query) => $query->where('bodega_id', $request->integer('bodega_id')))
            ->when($request->filled('fabricante_id'), fn ($query) => $query->where('fabricante_id', $request->integer('fabricante_id')))
            ->when($request->filled('estado_item'), fn ($query) => $query->where('estado_item', $request->string('estado_item')))
            ->orderByRaw("CAST(
                CASE
                    WHEN codigo LIKE '%-IN-%' THEN SUBSTRING_INDEX(SUBSTRING_INDEX(codigo, '-IN-', -1), '-', 1)
                    WHEN codigo LIKE 'IN-%' THEN SUBSTRING(codigo, 4)
                    ELSE '0'
                END AS UNSIGNED
            ) ASC")
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        if ($q !== '') {
            $equipos->getCollection()->loadMissing('tipoEquipo');
        }

        $tiposClasesEmpresaId = $empresaId ?: $userEmpresaId ?: Empresa::orderBy('id')->value('id');
        $tipos = $this->queryTiposInventario($tiposClasesEmpresaId)->get(['id', 'nombre']);
        $clases = $this->queryClasesInventario($tiposClasesEmpresaId)->get(['id', 'nombre', 'tipo_equipo_id']);
        $empresas = Empresa::orderBy('nombre')->get(['id', 'nombre']);
        $sedesQuery = Sede::query()->orderBy('nombre');
        $bodegasQuery = Bodega::query()->orderBy('nombre');
        $oficinasQuery = Oficina::query()->orderBy('nombre');
        $espaciosQuery = Espacio::query()->orderBy('nombre');
        if ($empresaId) {
            $sedesQuery->where('empresa_id', $empresaId);
            $bodegasQuery->where('empresa_id', $empresaId);
            $oficinasQuery->where('empresa_id', $empresaId);
            $espaciosQuery->where('empresa_id', $empresaId);
        }
        $sedes = $sedesQuery->get(['id', 'nombre', 'empresa_id']);
        $bodegas = $bodegasQuery->get(['id', 'nombre', 'empresa_id', 'sede_id']);
        $oficinas = $oficinasQuery->get(['id', 'nombre', 'empresa_id', 'sede_id']);
        $espacios = $espaciosQuery->get(['id', 'nombre', 'empresa_id', 'sede_id']);
        $fabricantes = Fabricante::orderBy('name')->get(['id', 'name']);

        $codigosDisponiblesQuery = CodigoReutilizable::query()
            ->where('estado', 'disponible')
            ->when(
                $empresaId,
                fn ($q) => $q->where('codigo', 'like', $this->resolveEmpresaPrefijo($empresaId) . '-%-IN-%'),
                fn ($q) => $q->where('codigo', 'like', 'IN-%')
            );

        $codigosDisponibles = $codigosDisponiblesQuery
            ->limit(400)
            ->get(['id', 'codigo'])
            ->sortBy(fn ($r) => $this->extractInventarioCodigoSecuencia($r->codigo))
            ->values()
            ->take(50);

        return view('admin.equipos.index', compact('equipos', 'tipos', 'clases', 'empresas', 'sedes', 'bodegas', 'fabricantes', 'q', 'codigosDisponibles'));
    }

    public function create()
    {
        $this->authorize('create', Equipo::class);

        $empresaId = $this->empresaActivaId();
        $tipos = $this->queryTiposInventario($empresaId)->get(['id', 'nombre']);
        $clases = $this->queryClasesInventario($empresaId)->get(['id', 'nombre', 'tipo_equipo_id']);
        $empresaActiva = Empresa::query()->findOrFail($empresaId, ['id', 'nombre']);
        $empresas = collect([$empresaActiva]);
        $fabricantes = Fabricante::orderBy('name')->get(['id', 'name']);

        $codigoLocked = false;
        $codigoReutilizableId = null;
        $codigo = request()->query('codigo');

        if (is_string($codigo) && trim($codigo) !== '') {
            $codigo = trim($codigo);
            $codigoLocked = true;
            $codigoReutilizableId = request()->query('codigo_reutilizable_id');
        } else {
            $codigo = '';
        }

        $codigoPrefijo = $this->resolveEmpresaPrefijo($empresaId);

        return view('admin.equipos.create', compact('tipos', 'clases', 'empresas', 'fabricantes', 'codigo', 'codigoLocked', 'codigoReutilizableId', 'codigoPrefijo', 'empresaId'));
    }

    public function codigoSugerido(Request $request)
    {
        $this->authorize('create', Equipo::class);

        $tipoEquipoId = $request->filled('tipo_equipo_id') ? $request->integer('tipo_equipo_id') : null;
        $claseEquipoId = $request->filled('clase_equipo_id') ? $request->integer('clase_equipo_id') : null;
        $empresaId = $this->empresaActivaId();

        $codigo = $this->buildNextEquipmentCode(
            $tipoEquipoId,
            $empresaId,
            'IN',
            $claseEquipoId,
            null
        );

        return response()->json(['codigo' => $codigo]);
    }

    public function usarCodigo(string $codigo)
    {
        $this->authorize('create', Equipo::class);

        $codigo = trim($codigo);

        $reg = CodigoReutilizable::query()
            ->where('codigo', $codigo)
            ->where('estado', 'disponible')
            ->first();

        if (!$reg) {
            return redirect()->route('equipos.index')->with('error', '❌ Ese código ya no está disponible');
        }

        return redirect()->route('equipos.create', ['codigo' => $codigo, 'codigo_reutilizable_id' => $reg->id]);
    }

    public function store(StoreEquipoRequest $request)
    {
        $empresaActivaId = $this->empresaActivaId();

        $codigoReutilizableId = $request->filled('codigo_reutilizable_id') ? (int) $request->input('codigo_reutilizable_id') : null;
        $prefijo = $this->resolveEmpresaPrefijo($empresaActivaId);
        if (!$codigoReutilizableId) {
            $codigoInput = $this->buildNextEquipmentCode(
                $request->integer('tipo_equipo_id'),
                $empresaActivaId,
                'IN',
                $request->integer('clase_equipo_id'),
                (string) $request->input('nombre', '')
            );

            $request->merge(['codigo' => $codigoInput]);
            $request->validate([
                'codigo' => ['required', 'string', 'max:100', 'starts_with:' . $prefijo . '-', 'unique:equipos,codigo', 'unique:equipos,serial'],
            ]);
        }

        DB::transaction(function () use ($request, $empresaActivaId) {
            $codigoReutilizableId = $request->filled('codigo_reutilizable_id') ? (int) $request->input('codigo_reutilizable_id') : null;
            $codigoFinal = $request->input('codigo');

            if ($codigoReutilizableId) {
                $reg = CodigoReutilizable::query()->lockForUpdate()->find($codigoReutilizableId);
                if (!$reg || $reg->estado !== 'disponible') {
                    abort(422);
                }
                $codigoFinal = $reg->codigo;
            }

            $request->merge(['codigo' => $codigoFinal]);
            $request->validate([
                'codigo' => ['required', 'string', 'max:100', 'unique:equipos,codigo', 'unique:equipos,serial'],
            ]);

            $equipo = new Equipo();
            $equipo->codigo = $codigoFinal;
            $equipo->nombre = $request->input('nombre');
            $equipo->serial = $codigoFinal;
            $equipo->descripcion = $request->input('descripcion');
            $equipo->activo = true;

            $equipo->tipo_equipo_id = $request->integer('tipo_equipo_id');
            $equipo->clase_equipo_id = $request->integer('clase_equipo_id');

            $equipo->estado_item = $request->input('estado_item');
            $equipo->observacion = $request->input('observacion');

            $equipo->fabricante_id = $request->filled('fabricante_id') ? $request->integer('fabricante_id') : null;

            $equipo->empresa_id = $empresaActivaId;
            $equipo->sede_id = $request->filled('sede_id') ? $request->integer('sede_id') : null;
            $equipo->ubicacion_tipo = $request->filled('ubicacion_tipo') ? $request->input('ubicacion_tipo') : null;
            $equipo->va_a_bodega = $request->input('ubicacion_tipo') === 'bodega';
            $equipo->bodega_id = ($request->input('ubicacion_tipo') === 'bodega' && $request->filled('bodega_id')) ? $request->integer('bodega_id') : null;
            $equipo->oficina_id = ($request->input('ubicacion_tipo') === 'oficina' && $request->filled('oficina_id')) ? $request->integer('oficina_id') : null;
            $equipo->espacio_id = ($request->input('ubicacion_tipo') === 'espacio' && $request->filled('espacio_id')) ? $request->integer('espacio_id') : null;

            $equipo->vida_util = $request->filled('vida_util') ? $request->integer('vida_util') : null;
            $equipo->fecha_fabricacion = $request->input('fecha_fabricacion');
            $equipo->fecha_uso = $request->input('fecha_uso');
            $equipo->tipo_uso = $request->input('tipo_uso');
            $equipo->tipo_uso_otro = $request->input('tipo_uso_otro');

            $equipo->es_kit = $request->boolean('es_kit');
            $equipo->kit_nombre = $request->input('kit_nombre');
            $equipo->kit_cantidad = $request->filled('kit_cantidad') ? $request->integer('kit_cantidad') : null;

            $equipo->certificacion_descripcion = $request->input('certificacion_descripcion');
            $equipo->especificaciones_tecnicas = $request->input('especificaciones_tecnicas');

            $equipo->valor_equipo = $request->input('valor_equipo');
            $equipo->numero_factura = $request->input('numero_factura');
            $equipo->fecha_compra = $request->input('fecha_compra');
            $equipo->lote = $request->input('lote');

            $equipo->tiene_resistencia = $request->boolean('tiene_resistencia');
            $equipo->resistencia_descripcion = $request->input('resistencia_descripcion');

            $equipo->tiene_manual_fabricante = $request->boolean('tiene_manual_fabricante');
            $equipo->tiene_certificacion_fabricante = $request->boolean('tiene_certificacion_fabricante');

            $equipo->save();

            if ($codigoReutilizableId) {
                CodigoReutilizable::where('id', $codigoReutilizableId)->update([
                    'estado' => 'usado',
                    'reservado_por' => null,
                    'reservado_en' => null,
                    'usado_en' => now(),
                ]);
            }

            $this->storeImages($request, $equipo);

            if ($equipo->es_kit) {
                $this->storeKit($request, $equipo);
            }
        });

        return redirect()->route('equipos.index')->with('success', 'Equipo creado correctamente.');
    }

    public function edit(Equipo $equipo)
    {
        $this->authorize('update', $equipo);

        $empresaId = $this->empresaActivaId();
        if ($equipo->empresa_id && (int) $equipo->empresa_id !== (int) $empresaId) {
            abort(404);
        }
        $tipos = $this->queryTiposInventario($empresaId)
            ->when($equipo->tipo_equipo_id, fn ($q) => $q->orWhere('id', $equipo->tipo_equipo_id))
            ->get(['id', 'nombre']);
        $clases = $this->queryClasesInventario($empresaId)
            ->when($equipo->clase_equipo_id, fn ($q) => $q->orWhere('id', $equipo->clase_equipo_id))
            ->get(['id', 'nombre', 'tipo_equipo_id']);
        $empresaActiva = Empresa::query()->findOrFail($empresaId, ['id', 'nombre']);
        $empresas = collect([$empresaActiva]);
        $bodegas = Bodega::orderBy('nombre')->get(['id', 'nombre', 'empresa_id', 'sede_id']);
        $oficinas = Oficina::orderBy('nombre')->get(['id', 'nombre', 'empresa_id', 'sede_id']);
        $espacios = Espacio::orderBy('nombre')->get(['id', 'nombre', 'empresa_id', 'sede_id']);
        $fabricantes = Fabricante::orderBy('name')->get(['id', 'name']);

        $equipo->load(['empresa', 'sede', 'bodega', 'fabricante', 'imagenes', 'kitItems']);

        $codigoPrefijo = $this->resolveEmpresaPrefijo($empresaId);

        return view('admin.equipos.edit', compact('equipo', 'tipos', 'clases', 'empresas', 'bodegas', 'oficinas', 'espacios', 'fabricantes', 'codigoPrefijo'));
    }

    public function show(Equipo $equipo)
    {
        $this->authorize('view', $equipo);

        $equipo->load(['tipoEquipo', 'claseEquipo', 'empresa', 'sede', 'bodega', 'fabricante', 'imagenes', 'kitItems', 'archivos']);

        return view('admin.equipos.show', compact('equipo'));
    }

    public function storeArchivo(Request $request, Equipo $equipo)
    {
        $this->authorize('update', $equipo);

        $request->validate([
            'archivo_nombre' => ['required', 'string', 'max:200'],
            'archivo_file' => ['required', 'file', 'max:10240'],
        ]);

        $file = $request->file('archivo_file');
        $mime = (string) ($file->getClientMimeType() ?? '');

        $isImage = str_starts_with($mime, 'image/');
        if ($isImage) {
            $request->validate([
                'archivo_file' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ]);

            $path = $file->store('equipos/certificacion', 'public');
            EquipoImagen::create([
                'equipo_id' => $equipo->id,
                'tipo' => 'certificacion_evidencia',
                'path' => $path,
            ]);

            return redirect()->route('equipos.show', $equipo)->with('success', 'Evidencia agregada correctamente.');
        }

        $path = UploadedFileStorage::storePublicDocument($file, 'equipos/archivos');

        EquipoArchivo::create([
            'equipo_id' => $equipo->id,
            'nombre' => $request->input('archivo_nombre'),
            'path' => $path,
            'mime' => $mime ?: null,
            'size' => $file->getSize(),
            'original_name' => $file->getClientOriginalName(),
        ]);

        return redirect()->route('equipos.show', $equipo)->with('success', 'Archivo agregado correctamente.');
    }

    public function downloadArchivo(EquipoArchivo $archivo)
    {
        $archivo->loadMissing('equipo');
        if (!$archivo->equipo) {
            abort(404);
        }

        $this->authorize('view', $archivo->equipo);

        $path = str_replace('\\', '/', ltrim((string) $archivo->path, '/'));
        if ($path === '' || str_contains($path, '..') || SensitiveDocumentStorage::isSensitivePath($path)) {
            abort(404);
        }

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->download(
            $path,
            $archivo->original_name ?: $archivo->nombre,
            UploadedFileStorage::secureDownloadHeaders()
        );
    }

    public function destroyArchivo(Equipo $equipo, EquipoArchivo $archivo)
    {
        $this->authorize('update', $equipo);

        if ((int) $archivo->equipo_id !== (int) $equipo->id) {
            abort(404);
        }

        if ($archivo->path) {
            try {
                Storage::disk('public')->delete($archivo->path);
            } catch (\Throwable $e) {
            }
        }

        $archivo->delete();

        return redirect()->route('equipos.show', $equipo)->with('success', 'Archivo eliminado correctamente.');
    }

    public function destroyEvidencia(Equipo $equipo, EquipoImagen $imagen)
    {
        $this->authorize('update', $equipo);

        if ((int) $imagen->equipo_id !== (int) $equipo->id) {
            abort(404);
        }

        if (!in_array($imagen->tipo, ['certificacion_evidencia', 'general', 'etiqueta', 'kit_general'], true)) {
            abort(404);
        }

        if ($imagen->path) {
            try {
                Storage::disk('public')->delete($imagen->path);
            } catch (\Throwable $e) {
            }
        }

        $imagen->delete();

        return redirect()->route('equipos.show', $equipo)->with('success', 'Evidencia eliminada correctamente.');
    }

    public function destroy(Equipo $equipo)
    {
        $this->authorize('delete', $equipo);

        $equipo->load(['imagenes', 'kitItems', 'archivos']);

        if ($equipo->codigo) {
            CodigoReutilizable::updateOrCreate([
                'codigo' => $equipo->codigo,
            ], [
                'estado' => 'disponible',
                'reservado_por' => null,
                'reservado_en' => null,
                'usado_en' => null,
            ]);
        }

        DB::transaction(function () use ($equipo) {
            foreach ($equipo->imagenes as $img) {
                if ($img->path) {
                    try {
                        Storage::disk('public')->delete($img->path);
                    } catch (\Throwable $e) {
                    }
                }
                $img->delete();
            }

            foreach ($equipo->archivos as $archivo) {
                if ($archivo->path) {
                    try {
                        Storage::disk('public')->delete($archivo->path);
                    } catch (\Throwable $e) {
                    }
                }
                $archivo->delete();
            }

            $equipo->kitItems()->delete();
            $equipo->delete();
        });

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Equipo eliminado correctamente.',
                'redirect' => route('equipos.index'),
            ]);
        }

        return redirect()->route('equipos.index')->with('success', 'Equipo eliminado correctamente.');
    }

    public function update(UpdateEquipoRequest $request, Equipo $equipo)
    {
        $empresaActivaId = $this->empresaActivaId();
        if ($equipo->empresa_id && (int) $equipo->empresa_id !== (int) $empresaActivaId) {
            abort(404);
        }

        $prefijo = $this->resolveEmpresaPrefijo($empresaActivaId);
        $codigoInput = $this->normalizeCodigo((string) $request->input('codigo', ''));

        if ($codigoInput === '') {
            $codigoInput = (string) ($equipo->codigo ?: $this->buildNextEquipmentCode(
                $request->integer('tipo_equipo_id'),
                $empresaActivaId,
                'IN',
                $request->integer('clase_equipo_id'),
                (string) $request->input('nombre', '')
            ));
        }
        $codigoInput = $this->ensureCodigoPrefix($codigoInput, $prefijo);

        $request->merge(['codigo' => $codigoInput]);
        $request->validate([
            'codigo' => ['required', 'string', 'max:100', 'starts_with:' . $prefijo . '-', 'unique:equipos,codigo,' . $equipo->id, 'unique:equipos,serial,' . $equipo->id],
        ]);

        $esKit = $request->boolean('es_kit');

        DB::transaction(function () use ($request, $equipo, $esKit, $empresaActivaId) {
            $equipo->fill([
                'codigo' => $request->input('codigo'),
                'serial' => $request->input('codigo'),
                'nombre' => $request->input('nombre'),
                'descripcion' => $request->input('descripcion'),
                'tipo_equipo_id' => $request->integer('tipo_equipo_id'),
                'clase_equipo_id' => $request->integer('clase_equipo_id'),
                'estado_item' => $request->input('estado_item'),
                'observacion' => $request->input('observacion'),
                'fabricante_id' => $request->filled('fabricante_id') ? $request->integer('fabricante_id') : null,
                'empresa_id' => $empresaActivaId,
                'sede_id' => $request->filled('sede_id') ? $request->integer('sede_id') : null,
                'ubicacion_tipo' => $request->filled('ubicacion_tipo') ? $request->input('ubicacion_tipo') : null,
                'va_a_bodega' => $request->input('ubicacion_tipo') === 'bodega',
                'bodega_id' => ($request->input('ubicacion_tipo') === 'bodega' && $request->filled('bodega_id')) ? $request->integer('bodega_id') : null,
                'oficina_id' => ($request->input('ubicacion_tipo') === 'oficina' && $request->filled('oficina_id')) ? $request->integer('oficina_id') : null,
                'espacio_id' => ($request->input('ubicacion_tipo') === 'espacio' && $request->filled('espacio_id')) ? $request->integer('espacio_id') : null,
                'vida_util' => $request->filled('vida_util') ? $request->integer('vida_util') : null,
                'fecha_fabricacion' => $request->input('fecha_fabricacion'),
                'fecha_uso' => $request->input('fecha_uso'),
                'tipo_uso' => $request->input('tipo_uso'),
                'tipo_uso_otro' => $request->input('tipo_uso_otro'),
                'es_kit' => $request->boolean('es_kit'),
                'kit_nombre' => $request->input('kit_nombre'),
                'kit_cantidad' => $request->filled('kit_cantidad') ? $request->integer('kit_cantidad') : null,
                'certificacion_descripcion' => $request->input('certificacion_descripcion'),
                'especificaciones_tecnicas' => $request->input('especificaciones_tecnicas'),
                'valor_equipo' => $request->input('valor_equipo'),
                'numero_factura' => $request->input('numero_factura'),
                'fecha_compra' => $request->input('fecha_compra'),
                'lote' => $request->input('lote'),
                'tiene_resistencia' => $request->boolean('tiene_resistencia'),
                'resistencia_descripcion' => $request->input('resistencia_descripcion'),
                'tiene_manual_fabricante' => $request->boolean('tiene_manual_fabricante'),
                'tiene_certificacion_fabricante' => $request->boolean('tiene_certificacion_fabricante'),
            ]);
            $equipo->save();

            $deleteIds = (array) $request->input('delete_imagenes', []);
            if (!empty($deleteIds)) {
                $imgs = $equipo->imagenes()->whereIn('id', $deleteIds)->get();
                foreach ($imgs as $img) {
                    if ($img->path) {
                        try {
                            Storage::disk('public')->delete($img->path);
                        } catch (\Throwable $e) {
                        }
                    }
                    $img->delete();
                }
            }

            $this->storeImages($request, $equipo);

            if ($esKit) {
                if ($request->hasFile('kit_imagen_general')) {
                    $old = $equipo->imagenes()->where('tipo', 'kit_general')->get();
                    foreach ($old as $img) {
                        if ($img->path) {
                            try {
                                Storage::disk('public')->delete($img->path);
                            } catch (\Throwable $e) {
                            }
                        }
                        $img->delete();
                    }
                }

                $equipo->kitItems()->delete();
                $this->storeKit($request, $equipo);
            } else {
                $equipo->kitItems()->delete();
                $equipo->imagenes()->where('tipo', 'kit_general')->delete();
            }
        });

        return redirect()->route('equipos.index')->with('success', 'Equipo actualizado correctamente.');
    }

    private function nextCodigo(): string
    {
        return $this->buildNextEquipmentCode(null, EmpresaContext::empresaId(), 'IN', null, null);
    }

    private function buildNextEquipmentCode(?int $tipoEquipoId, ?int $empresaId, string $estado, ?int $claseEquipoId = null, ?string $equipoNombre = null): string
    {
        $prefijo = $this->resolveEmpresaPrefijo($empresaId);
        $estadoTag = strtoupper($estado) === 'IN'
            ? $this->empresaCodeTag($empresaId, 'inventory_tag', 'IN')
            : strtoupper($estado);
        $codigoClase = 'GEN';
        if ($claseEquipoId) {
            $clase = ClaseEquipo::query()->find($claseEquipoId);
            if ($clase) {
                $codigoClase = $this->codigoTagDesdeNombre((string) ($clase->alias ?: $clase->nombre));
            }
        } elseif ($tipoEquipoId) {
            $tipo = TipoEquipo::query()->find($tipoEquipoId);
            if ($tipo) {
                $codigoClase = $this->codigoTagDesdeNombre((string) ($tipo->alias ?: $tipo->nombre));
            }
        }

        $base = $prefijo . '-' . $codigoClase . '-' . $estadoTag . '-';
        // Secuencia única por empresa y prefijo: el número tras -IN- es correlativo entre todos los tipos/clases.
        $likePatron = $prefijo . '-%-' . $estadoTag . '-%';

        $existentes = Equipo::query()
            ->whereNotNull('codigo')
            ->when($empresaId, fn ($q) => $q->where(fn ($sub) => $sub->where('empresa_id', $empresaId)->orWhereNull('empresa_id')))
            ->where('codigo', 'like', $likePatron)
            ->pluck('codigo');

        $reutilCodigos = CodigoReutilizable::query()
            ->where('codigo', 'like', $likePatron)
            ->pluck('codigo');

        $regex = '/^' . preg_quote($prefijo, '/') . '-[A-Z0-9]+-' . preg_quote($estadoTag, '/') . '-(\d{1,6})(?:-\([^)]+\))?$/i';

        $maxNum = 0;
        foreach ($existentes->merge($reutilCodigos) as $cod) {
            if (preg_match($regex, (string) $cod, $match)) {
                $num = (int) $match[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }
        $next = $maxNum + 1;
        $final = $base . str_pad((string) $next, 4, '0', STR_PAD_LEFT);

        $nombre = strtoupper(trim((string) $equipoNombre));
        $nombre = preg_replace('/[^A-Z0-9]/', '', $nombre) ?: '';
        if ($nombre !== '') {
            $final .= '-(' . substr($nombre, 0, 18) . ')';
        }
        return $final;
    }

    private function codigoTagDesdeNombre(string $nombre): string
    {
        return strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $nombre) ?: 'GEN', 0, 4));
    }

    private function empresaCodeTag(?int $empresaId, string $key, string $default): string
    {
        if (!$empresaId) {
            return $default;
        }
        $empresa = Empresa::find($empresaId);
        $tag = strtoupper((string) ($empresa?->code_settings[$key] ?? $default));
        $tag = preg_replace('/[^A-Z0-9]/', '', $tag) ?: $default;
        return $tag;
    }

    /**
     * Número de secuencia del inventario (tramo -IN-NNNN), para ordenar y sincronizar huecos.
     */
    private function extractInventarioCodigoSecuencia(string $codigo): int
    {
        $codigo = strtoupper(trim($codigo));
        if (preg_match('/-IN-(\d{1,6})(?:-\([^)]+\))?$/', $codigo, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/^IN-(\d{1,})/', $codigo, $m)) {
            return (int) $m[1];
        }

        return 0;
    }

    /**
     * Rellena códigos reutilizables con huecos (PREF-GEN-IN-NNNN) según secuencia global de la empresa.
     */
    private function syncCodigosFaltantesParaPrefijo(?int $empresaId): void
    {
        if ($empresaId === null || ! Schema::hasTable('codigos_reutilizables')) {
            return;
        }

        $pref = strtoupper($this->resolveEmpresaPrefijo($empresaId));
        $estadoTag = $this->empresaCodeTag($empresaId, 'inventory_tag', 'IN');
        $like = $pref . '-%-' . $estadoTag . '-%';

        $nums = Equipo::query()
            ->whereNotNull('codigo')
            ->where('codigo', 'like', $like)
            ->when($empresaId, fn ($q) => $q->where(fn ($sub) => $sub->where('empresa_id', $empresaId)->orWhereNull('empresa_id')))
            ->pluck('codigo')
            ->map(fn ($c) => $this->extractInventarioCodigoSecuencia((string) $c))
            ->merge(
                CodigoReutilizable::query()
                    ->where('codigo', 'like', $like)
                    ->pluck('codigo')
                    ->map(fn ($c) => $this->extractInventarioCodigoSecuencia((string) $c))
            )
            ->filter(fn ($n) => $n >= 1)
            ->unique()
            ->values();

        if ($nums->isEmpty()) {
            return;
        }

        $max = min((int) $nums->max(), 9999);
        $have = array_fill(0, $max + 1, false);
        foreach ($nums as $n) {
            if ($n >= 1 && $n <= $max) {
                $have[$n] = true;
            }
        }

        $toInsert = [];
        for ($i = 1; $i <= $max; $i++) {
            if ($have[$i] === false) {
                $toInsert[] = [
                    'codigo' => $pref . '-GEN-IN-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                    'estado' => 'disponible',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if ($toInsert !== []) {
            CodigoReutilizable::query()->insertOrIgnore($toInsert);
        }
    }

    private function syncCodigosFaltantes(): void
    {
        if (!Schema::hasTable('codigos_reutilizables')) {
            return;
        }

        $maxCodigo = Equipo::query()
            ->whereNotNull('codigo')
            ->where('codigo', 'like', 'IN-%')
            ->selectRaw("MAX(CAST(SUBSTRING(codigo, 4) AS UNSIGNED)) as max_num")
            ->value('max_num');

        $maxSerial = Equipo::query()
            ->whereNotNull('serial')
            ->where('serial', 'like', 'IN-%')
            ->selectRaw("MAX(CAST(SUBSTRING(serial, 4) AS UNSIGNED)) as max_num")
            ->value('max_num');

        $max = max((int) $maxCodigo, (int) $maxSerial);
        if ($max <= 1) {
            return;
        }

        $max = min($max, 9999);

        $numsCodigo = Equipo::query()
            ->whereNotNull('codigo')
            ->where('codigo', 'like', 'IN-%')
            ->selectRaw("DISTINCT CAST(SUBSTRING(codigo, 4) AS UNSIGNED) as num")
            ->pluck('num')
            ->map(fn ($v) => (int) $v)
            ->all();

        $numsSerial = Equipo::query()
            ->whereNotNull('serial')
            ->where('serial', 'like', 'IN-%')
            ->selectRaw("DISTINCT CAST(SUBSTRING(serial, 4) AS UNSIGNED) as num")
            ->pluck('num')
            ->map(fn ($v) => (int) $v)
            ->all();

        $existingNums = array_fill(0, $max + 1, false);
        foreach ($numsCodigo as $n) {
            if ($n >= 1 && $n <= $max) {
                $existingNums[$n] = true;
            }
        }
        foreach ($numsSerial as $n) {
            if ($n >= 1 && $n <= $max) {
                $existingNums[$n] = true;
            }
        }

        $existingReusable = CodigoReutilizable::query()
            ->where('codigo', 'like', 'IN-%')
            ->pluck('codigo')
            ->map(function ($c) {
                $n = $this->extractCodigoNumero((string) $c);
                return $n ? (int) $n : null;
            })
            ->filter(fn ($n) => $n !== null)
            ->map(fn ($n) => (int) $n)
            ->all();

        $existingReusableNums = array_fill(0, $max + 1, false);
        foreach ($existingReusable as $n) {
            if ($n >= 1 && $n <= $max) {
                $existingReusableNums[$n] = true;
            }
        }

        $toInsert = [];
        for ($i = 1; $i <= $max; $i++) {
            if ($existingNums[$i] === false && $existingReusableNums[$i] === false) {
                $toInsert[] = [
                    'codigo' => 'IN-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                    'estado' => 'disponible',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($toInsert)) {
            CodigoReutilizable::query()->insertOrIgnore($toInsert);
        }
    }

    private function extractCodigoNumero(string $codigo): ?int
    {
        $codigo = trim($codigo);
        if (!preg_match('/^IN-(\d{1,})$/', $codigo, $m)) {
            return null;
        }
        return (int) $m[1];
    }

    private function resolveEmpresaPrefijo(?int $empresaId): string
    {
        if ($empresaId) {
            $empresa = Empresa::query()->find($empresaId);
            if ($empresa) {
                $raw = (string) ($empresa->prefijo ?: EmpresaContext::prefijo());
                $clean = strtoupper(preg_replace('/[^A-Z0-9]/', '', $raw) ?: 'EMP');
                return $clean !== '' ? $clean : 'EMP';
            }
        }

        return EmpresaContext::prefijo();
    }

    private function normalizeCodigo(string $codigo): string
    {
        $clean = strtoupper(trim($codigo));
        return preg_replace('/\s+/', '', $clean) ?? '';
    }

    private function ensureCodigoPrefix(string $codigo, string $prefijo): string
    {
        return str_starts_with($codigo, $prefijo . '-') ? $codigo : ($prefijo . '-' . ltrim($codigo, '-'));
    }

    private function storeImages(Request $request, Equipo $equipo): void
    {
        if ($request->hasFile('imagenes_general')) {
            foreach ((array) $request->file('imagenes_general') as $file) {
                $path = $file->store('equipos/general', 'public');
                EquipoImagen::create([
                    'equipo_id' => $equipo->id,
                    'tipo' => 'general',
                    'path' => $path,
                ]);
            }
        }

        if ($request->hasFile('imagenes_etiqueta')) {
            foreach ((array) $request->file('imagenes_etiqueta') as $file) {
                $path = $file->store('equipos/etiqueta', 'public');
                EquipoImagen::create([
                    'equipo_id' => $equipo->id,
                    'tipo' => 'etiqueta',
                    'path' => $path,
                ]);
            }
        }

        if ($request->hasFile('certificacion_evidencias')) {
            foreach ((array) $request->file('certificacion_evidencias') as $file) {
                $path = $file->store('equipos/certificacion', 'public');
                EquipoImagen::create([
                    'equipo_id' => $equipo->id,
                    'tipo' => 'certificacion_evidencia',
                    'path' => $path,
                ]);
            }
        }
    }

    private function storeKit(Request $request, Equipo $equipo): void
    {
        if ($request->hasFile('kit_imagen_general')) {
            $path = $request->file('kit_imagen_general')->store('equipos/kit', 'public');
            EquipoImagen::create([
                'equipo_id' => $equipo->id,
                'tipo' => 'kit_general',
                'path' => $path,
            ]);
        }

        $items = (array) $request->input('kit_items', []);
        foreach (array_values($items) as $item) {
            $nombre = trim((string) ($item['nombre'] ?? ''));
            if ($nombre === '') {
                continue;
            }

            $descripcion = trim((string) ($item['descripcion'] ?? ''));

            EquipoKitItem::create([
                'equipo_id' => $equipo->id,
                'nombre' => $nombre,
                'descripcion' => $descripcion !== '' ? $descripcion : $nombre,
                'foto_path' => 'N/A',
            ]);
        }
    }

    private function queryTiposInventario(?int $empresaId)
    {
        return TipoEquipo::when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->whereRaw('LOWER(nombre) NOT LIKE ?', ['%papeler%'])
            ->whereRaw('LOWER(COALESCE(alias, \'\')) NOT LIKE ?', ['%papeler%'])
            ->orderBy('nombre');
    }

    private function queryClasesInventario(?int $empresaId)
    {
        return ClaseEquipo::with('tipoEquipo')
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->whereHas('tipoEquipo', function ($t) {
                $t->whereRaw('LOWER(nombre) NOT LIKE ?', ['%papeler%'])
                    ->whereRaw('LOWER(COALESCE(alias, \'\')) NOT LIKE ?', ['%papeler%']);
            })
            ->whereRaw('LOWER(nombre) NOT LIKE ?', ['%papeler%'])
            ->whereRaw('LOWER(COALESCE(alias, \'\')) NOT LIKE ?', ['%papeler%'])
            ->orderBy('nombre');
    }
}
