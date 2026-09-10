<?php

namespace App\Http\Controllers;

use App\Models\Cargo;
use App\Models\Empresa;
use App\Models\User;
use App\Services\EmpresaContext;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

class CargoController extends Controller
{
    private function empresaActivaId(): ?int
    {
        return EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;
    }

    public function complete(Request $request)
    {
        $this->assertCanViewModule('cargos');
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $perPageRaw = (string) $request->query('per_page', '15');

        $perPageOptions = ['10', '15', '30', '50', '100', 'all'];
        $perPage = in_array($perPageRaw, $perPageOptions, true) ? $perPageRaw : '15';

        $empresaId = $this->empresaActivaId();
        if (!$empresaId) {
            abort(403, 'Debes seleccionar una empresa activa para gestionar cargos.');
        }

        $query = Cargo::withCount('users')->where('empresa_id', $empresaId);

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

        $chartTopUsersByCargo = (clone $filteredQuery)
            ->reorder()
            ->orderByDesc('users_count')
            ->limit(10)
            ->get()
            ->map(function ($c) {
                return [
                    'label' => \Illuminate\Support\Str::limit((string) $c->name, 34),
                    'count' => (int) $c->users_count,
                ];
            })
            ->values()
            ->all();

        if ($perPage === 'all') {
            $items = (clone $filteredQuery)->get();
            $cargos = new LengthAwarePaginator(
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
            $cargos = (clone $filteredQuery)->paginate((int) $perPage)->appends($request->query());
        }

        $totalCargos = (clone $filteredQuery)->count();
        $cargoIds = (clone $filteredQuery)->reorder()->pluck('id');
        $totalUsersAssigned = $cargoIds->isEmpty()
            ? 0
            : (int) User::query()->whereIn('cargo_id', $cargoIds)->count();
        $cargosActive = (clone $filteredQuery)->where('activo', true)->count();
        $cargosInactive = (clone $filteredQuery)->where('activo', false)->count();
        $stats = [
            'total' => $totalCargos,
            'users_assigned' => $totalUsersAssigned,
            'avg_users_per_cargo' => $totalCargos > 0 ? round($totalUsersAssigned / $totalCargos, 1) : 0,
            'cargos_active' => $cargosActive,
            'cargos_inactive' => $cargosInactive,
        ];

        return view('admin.cargos.complete', compact('cargos', 'stats', 'q', 'status', 'perPage', 'chartTopUsersByCargo'));
    }

    public function index()
    {
        $this->assertCanViewModule('cargos');
        $empresaId = $this->empresaActivaId();
        if (!$empresaId) {
            abort(403);
        }
        $cargos = Cargo::withCount('users')->where('empresa_id', $empresaId)->get();

        return view('admin.cargos.index', compact('cargos'));
    }

    public function create()
    {
        $this->assertCanEditModule('cargos');
        $this->assertTenantEmpresaId();
        return view('admin.cargos.create');
    }

    public function store(Request $request)
    {
        $this->assertCanEditModule('cargos');
        $empresaId = $this->empresaActivaId();
        if (!$empresaId) {
            abort(403);
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('cargos', 'name')->where('empresa_id', $empresaId),
            ],
            'description' => 'nullable|string',
        ]);

        $cargo = Cargo::create([
            'empresa_id' => $empresaId,
            'name' => $request->name,
            'description' => $request->description,
            'activo' => true,
        ]);

        return redirect()->route('cargos.complete')->with('success', '✅ Cargo creado exitosamente');
    }

    public function edit(Cargo $cargo)
    {
        $this->assertCanViewModule('cargos');
        $empresaId = $this->empresaActivaId();
        if (!$empresaId || (int) $cargo->empresa_id !== (int) $empresaId) {
            abort(404);
        }
        return response()->json($cargo->only(['id', 'name', 'description', 'activo']));
    }

    public function update(Request $request, Cargo $cargo)
    {
        $this->assertCanEditModule('cargos');
        $empresaId = $this->empresaActivaId();
        if (!$empresaId || (int) $cargo->empresa_id !== (int) $empresaId) {
            abort(404);
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('cargos', 'name')->where('empresa_id', $empresaId)->ignore($cargo->id),
            ],
            'description' => 'nullable|string',
        ]);

        $cargo->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('cargos.complete')->with('success', '✅ Cargo actualizado exitosamente');
    }

    public function destroy(Cargo $cargo)
    {
        $this->assertCanEditModule('cargos');
        $empresaId = $this->empresaActivaId();
        if (!$empresaId || (int) $cargo->empresa_id !== (int) $empresaId) {
            abort(404);
        }

        try {
            if (method_exists($cargo, 'users') && $cargo->users()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar: hay usuarios asignados a este cargo.',
                ], 422);
            }
            $cargo->delete();
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'No se pudo eliminar el cargo.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cargo eliminado exitosamente.',
        ]);
    }

    public function toggleStatus(Cargo $cargo)
    {
        $this->assertCanEditModule('cargos');
        $empresaId = $this->empresaActivaId();
        if (!$empresaId || (int) $cargo->empresa_id !== (int) $empresaId) {
            abort(404);
        }
        $cargo->update(['activo' => ! $cargo->activo]);

        $status = $cargo->activo ? 'activado' : 'desactivado';

        return response()->json([
            'success' => true,
            'message' => "✅ Cargo {$status} exitosamente.",
        ]);
    }
}
