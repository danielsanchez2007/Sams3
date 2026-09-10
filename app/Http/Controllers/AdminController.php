<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use App\Models\ClaseEquipo;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\Grupo;
use App\Models\Role;
use App\Models\Sede;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Services\EmpresaContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AdminController extends Controller
{
    /**
     * Show the admin dashboard.
     * Muestra solo la información de la empresa activa (cuando hay contexto de empresa).
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        $empresaId = EmpresaContext::empresaId() ?? Auth::user()?->empresa_id;
        $principalId = Cache::remember('sams_empresa_principal_id', 3600, fn () => Empresa::query()->orderBy('id')->value('id'));
        $empresaActivaId = $empresaId ?: $principalId;

        $equiposBase = Equipo::query();
        $usersBase = User::query();
        $tiposBase = TipoEquipo::query();
        $clasesBase = ClaseEquipo::query();

        if ($empresaActivaId) {
            $esPrincipal = $principalId && $empresaActivaId == $principalId;
            $equiposBase->when(true, function ($q) use ($empresaActivaId, $esPrincipal) {
                if ($esPrincipal) {
                    $q->where(fn ($sub) => $sub->where('empresa_id', $empresaActivaId)->orWhereNull('empresa_id'));
                } else {
                    $q->where('empresa_id', $empresaActivaId);
                }
            });
            $usersBase->where('empresa_id', $empresaActivaId);
            $tiposBase->where('empresa_id', $empresaActivaId);
            $clasesBase->where('empresa_id', $empresaActivaId);
        }

        $stats = [
            'users' => $usersBase->count(),
            'equipos' => $equiposBase->where('activo', true)->whereNotNull('codigo')->count(),
            'roles' => Role::count(),
            'grupos' => Grupo::count(),
            'tiposEquipos' => $tiposBase->count(),
            'clasesEquipos' => $clasesBase->count(),
        ];

        $empresas = Empresa::query()
            ->select(['id', 'nombre'])
            ->orderBy('nombre')
            ->get();

        $bodegas = Bodega::query()
            ->select(['id', 'empresa_id', 'nombre'])
            ->when($empresaActivaId, fn ($q) => $q->where('empresa_id', $empresaActivaId))
            ->orderBy('nombre')
            ->get();

        $sedes = Sede::query()
            ->select(['id', 'empresa_id', 'nombre'])
            ->when($empresaActivaId, fn ($q) => $q->where('empresa_id', $empresaActivaId))
            ->orderBy('nombre')
            ->get();

        return view('admin.dashboard', compact('stats', 'empresas', 'bodegas', 'sedes', 'empresaActivaId'));
    }

    /**
     * Datos agregados para gráficos del dashboard (sin cargar todos los equipos en el HTML).
     */
    public function equiposChartData(Request $request)
    {
        $origenSql = <<<'SQL'
SUM(CASE WHEN codigo IS NOT NULL AND codigo LIKE 'IN-%' THEN 1 ELSE 0 END) AS c_inventario,
SUM(CASE WHEN codigo IS NOT NULL AND codigo LIKE 'DB-%' THEN 1 ELSE 0 END) AS c_baja,
SUM(CASE WHEN codigo IS NOT NULL AND codigo LIKE 'AUD-%' THEN 1 ELSE 0 END) AS c_auditoria,
SUM(CASE WHEN codigo IS NOT NULL AND codigo LIKE 'MD-%' THEN 1 ELSE 0 END) AS c_md,
SUM(CASE WHEN codigo IS NULL OR (codigo NOT LIKE 'IN-%' AND codigo NOT LIKE 'DB-%' AND codigo NOT LIKE 'AUD-%' AND codigo NOT LIKE 'MD-%') THEN 1 ELSE 0 END) AS c_otros
SQL;

        $base = $this->buildEquiposResumenQuery();
        $row = (clone $base)->selectRaw($origenSql)->first();

        $origenCounts = [
            'Inventario' => (int) ($row->c_inventario ?? 0),
            'Baja' => (int) ($row->c_baja ?? 0),
            'Auditoría' => (int) ($row->c_auditoria ?? 0),
            'Material didáctico' => (int) ($row->c_md ?? 0),
            'Otros' => (int) ($row->c_otros ?? 0),
        ];

        $topRows = (clone $base)
            ->selectRaw('empresa_id, COUNT(*) AS c')
            ->groupBy('empresa_id')
            ->orderByDesc('c')
            ->limit(5)
            ->get();

        $empresaIds = $topRows->pluck('empresa_id')->filter(fn ($id) => $id !== null)->unique()->values()->all();
        $empresaNombres = $empresaIds === []
            ? collect()
            : Empresa::query()->whereIn('id', $empresaIds)->pluck('nombre', 'id');

        $empresaTop = $topRows->map(function ($r) use ($empresaNombres) {
            $id = $r->empresa_id;

            return [
                'empresa_id' => $id,
                'nombre' => $id === null ? 'Sin empresa' : ($empresaNombres[$id] ?? 'Empresa #' . $id),
                'count' => (int) $r->c,
            ];
        })->values()->all();

        $filtroEmpresa = $request->query('empresa_id');
        $filtroSede = $request->query('sede_id');
        $filtroBodega = $request->query('bodega_id');

        $distribQ = $this->buildEquiposResumenQuery();
        if ($filtroEmpresa !== null && $filtroEmpresa !== '') {
            $distribQ->where('empresa_id', (int) $filtroEmpresa);
        }
        if ($filtroSede !== null && $filtroSede !== '') {
            $distribQ->where('sede_id', (int) $filtroSede);
        }
        if ($filtroBodega !== null && $filtroBodega !== '') {
            $distribQ->where('bodega_id', (int) $filtroBodega);
        }

        $drow = (clone $distribQ)->selectRaw($origenSql)->first();
        $distribucion = [
            'Inventario' => (int) ($drow->c_inventario ?? 0),
            'Baja' => (int) ($drow->c_baja ?? 0),
            'Auditoría' => (int) ($drow->c_auditoria ?? 0),
            'Material didáctico' => (int) ($drow->c_md ?? 0),
            'Otros' => (int) ($drow->c_otros ?? 0),
        ];

        return response()->json([
            'origen_counts' => $origenCounts,
            'empresa_top' => $empresaTop,
            'distribucion' => $distribucion,
        ]);
    }

    /**
     * Misma base que antes para @json($equiposResumen): equipos visibles según contexto de empresa.
     */
    private function buildEquiposResumenQuery(): Builder
    {
        $empresaId = EmpresaContext::empresaId() ?? Auth::user()?->empresa_id;
        $principalId = Cache::remember('sams_empresa_principal_id', 3600, fn () => Empresa::query()->orderBy('id')->value('id'));
        $empresaActivaId = $empresaId ?: $principalId;

        $q = Equipo::query();

        if ($empresaActivaId) {
            $esPrincipal = $principalId && $empresaActivaId == $principalId;
            if ($esPrincipal) {
                $q->where(fn ($sub) => $sub->where('empresa_id', $empresaActivaId)->orWhereNull('empresa_id'));
            } else {
                $q->where('empresa_id', $empresaActivaId);
            }
        }

        return $q;
    }
}
