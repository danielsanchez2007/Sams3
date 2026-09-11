<?php

namespace App\Http\Controllers;

use App\Models\Grupo;
use App\Models\User;
use App\Services\EmpresaContext;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class GrupoController extends Controller
{
    public function complete(Request $request)
    {
        $this->assertCanViewModule('grupos');
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $perPageRaw = (string) $request->query('per_page', '15');

        $perPageOptions = ['10', '15', '30', '50', '100', 'all'];
        $perPage = in_array($perPageRaw, $perPageOptions, true) ? $perPageRaw : '15';

        $empresaId = EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;
        if (!$empresaId) {
            abort(403, 'Debes seleccionar una empresa activa para gestionar grupos.');
        }

        $query = Grupo::withCount('users')->with('leader')->where('empresa_id', $empresaId);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhereHas('leader', function ($l) use ($q) {
                        $l->where('name', 'like', "%{$q}%")
                            ->orWhere('last_name', 'like', "%{$q}%");
                    });
            });
        }

        if ($status === 'active') {
            $query->where('activo', true);
        } elseif ($status === 'inactive') {
            $query->where('activo', false);
        }

        $query->orderBy('id', 'desc');

        if ($perPage === 'all') {
            $items = $query->get();
            $grupos = new LengthAwarePaginator(
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
            $grupos = $query->paginate((int) $perPage)->appends($request->query());
        }

        $leaderIds = Grupo::where('empresa_id', $empresaId)->whereNotNull('leader_id')->pluck('leader_id')->unique()->values();
        $users = User::where('active', true)->where('empresa_id', $empresaId)->get();

        $totalGrupos = Grupo::where('empresa_id', $empresaId)->count();
        $activeUsers = User::where('active', true)->where('empresa_id', $empresaId)->count();
        $stats = [
            'total_grupos' => $totalGrupos,
            'active_users' => $activeUsers,
            'avg_users_per_group' => $totalGrupos > 0 ? round($activeUsers / $totalGrupos, 1) : 0,
        ];

        return view('admin.grupos.complete', compact('grupos', 'users', 'leaderIds', 'stats', 'q', 'status', 'perPage'));
    }

    public function index()
    {
        return redirect()->route('grupos.complete');
    }

    public function create()
    {
        return redirect()->route('grupos.complete');
    }

    public function store(Request $request)
    {
        $this->assertCanEditModule('grupos');
        $empresaId = EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;
        if (!$empresaId) {
            abort(403);
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('grupos', 'name')->where('empresa_id', $empresaId),
            ],
            'description' => 'nullable|string',
            'leader_id' => ['nullable', 'exists:users,id', Rule::exists('users', 'id')->where('empresa_id', $empresaId)],
            'member_ids' => 'nullable|array',
            'member_ids.*' => ['integer', Rule::exists('users', 'id')->where('empresa_id', $empresaId)],
        ]);

        if ($request->filled('leader_id')) {
            $leaderId = (int) $request->leader_id;
            $leaderAlready = Grupo::where('empresa_id', $empresaId)->where('leader_id', $leaderId)->exists();
            if ($leaderAlready) {
                return back()->withErrors([
                    'leader_id' => 'Este usuario ya es líder de otro grupo.'
                ])->withInput();
            }
        }

        $grupo = Grupo::create([
            'empresa_id' => $empresaId,
            'name' => $request->name,
            'description' => $request->description,
            'leader_id' => $request->leader_id,
            'activo' => true
        ]);

        $memberIds = collect($request->input('member_ids', []))
            ->filter(fn($id) => $id !== null && $id !== '')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($request->filled('leader_id')) {
            $memberIds = $memberIds->push((int) $request->leader_id)->unique()->values();
        }

        $blockedLeaderIds = Grupo::where('empresa_id', $empresaId)->whereNotNull('leader_id')->pluck('leader_id')->unique();
        if ($request->filled('leader_id')) {
            $blockedLeaderIds = $blockedLeaderIds->reject(fn($id) => (int) $id === (int) $request->leader_id);
        }
        $memberIds = $memberIds->reject(fn($id) => $blockedLeaderIds->contains($id))->values();

        if ($memberIds->isNotEmpty()) {
            User::where('empresa_id', $empresaId)->whereIn('id', $memberIds)->update(['grupo_id' => $grupo->id]);
        }

        return redirect()->route('grupos.complete')->with('success', '✅ Grupo creado exitosamente');
    }

    public function edit(Grupo $grupo)
    {
        $this->assertCanViewModule('grupos');
        $empresaId = EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;
        if (!$empresaId || (int) $grupo->empresa_id !== (int) $empresaId) {
            abort(404);
        }
        $grupo->loadCount('users');
        return response()->json($grupo->only(['id', 'name', 'description', 'leader_id', 'activo']) + [
            'users_count' => $grupo->users_count,
            'member_ids' => $grupo->users()->pluck('id')->values()
        ]);
    }

    public function update(Request $request, Grupo $grupo)
    {
        $this->assertCanEditModule('grupos');
        $empresaId = EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;
        if (!$empresaId || (int) $grupo->empresa_id !== (int) $empresaId) {
            abort(404);
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('grupos', 'name')->where('empresa_id', $empresaId)->ignore($grupo->id),
            ],
            'description' => 'nullable|string',
            'leader_id' => ['nullable', Rule::exists('users', 'id')->where('empresa_id', $empresaId)],
            'member_ids' => 'nullable|array',
            'member_ids.*' => ['integer', Rule::exists('users', 'id')->where('empresa_id', $empresaId)],
        ]);

        if ($request->filled('leader_id')) {
            $leaderId = (int) $request->leader_id;
            $leaderAlready = Grupo::where('empresa_id', $empresaId)->where('leader_id', $leaderId)
                ->where('id', '!=', $grupo->id)
                ->exists();
            if ($leaderAlready) {
                return back()->withErrors([
                    'leader_id' => 'Este usuario ya es líder de otro grupo.'
                ])->withInput();
            }
        }

        $grupo->update([
            'name' => $request->name,
            'description' => $request->description,
            'leader_id' => $request->leader_id
        ]);

        $memberIds = collect($request->input('member_ids', []))
            ->filter(fn($id) => $id !== null && $id !== '')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($request->filled('leader_id')) {
            $memberIds = $memberIds->push((int) $request->leader_id)->unique()->values();
        }

        $blockedLeaderIds = Grupo::where('empresa_id', $empresaId)->whereNotNull('leader_id')
            ->where('id', '!=', $grupo->id)
            ->pluck('leader_id')
            ->unique();
        $memberIds = $memberIds->reject(fn($id) => $blockedLeaderIds->contains($id))->values();

        User::where('empresa_id', $empresaId)->where('grupo_id', $grupo->id)
            ->when($memberIds->isNotEmpty(), fn($q) => $q->whereNotIn('id', $memberIds))
            ->update(['grupo_id' => null]);

        if ($memberIds->isNotEmpty()) {
            User::where('empresa_id', $empresaId)->whereIn('id', $memberIds)->update(['grupo_id' => $grupo->id]);
        }

        return redirect()->route('grupos.complete')->with('success', '✅ Grupo actualizado exitosamente');
    }

    public function destroy(Grupo $grupo)
    {
        $this->assertCanEditModule('grupos');
        $empresaId = EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;
        if (!$empresaId || (int) $grupo->empresa_id !== (int) $empresaId) {
            abort(404);
        }

        try {
            DB::transaction(function () use ($grupo) {
                if (Schema::hasColumn('users', 'grupo_id')) {
                    DB::table('users')->where('grupo_id', $grupo->id)->update(['grupo_id' => null]);
                }
                if (Schema::hasTable('grupo_members') && Schema::hasColumn('grupo_members', 'grupo_id')) {
                    DB::table('grupo_members')->where('grupo_id', $grupo->id)->delete();
                }
                $grupo->update(['leader_id' => null]);
                $grupo->delete();
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'No se pudo eliminar el grupo.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Grupo eliminado exitosamente.'
        ]);
    }

    public function toggleStatus(Grupo $grupo)
    {
        $this->assertCanEditModule('grupos');
        $empresaId = EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;
        if (!$empresaId || (int) $grupo->empresa_id !== (int) $empresaId) {
            abort(404);
        }
        $grupo->update(['activo' => !$grupo->activo]);

        $status = $grupo->activo ? 'activado' : 'desactivado';
        return response()->json([
            'success' => true,
            'message' => "✅ Grupo {$status} exitosamente."
        ]);
    }

    public function usersJson(Grupo $grupo)
    {
        $this->assertCanViewModule('grupos');
        $empresaId = EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;
        if (!$empresaId || (int) $grupo->empresa_id !== (int) $empresaId) {
            abort(404);
        }
        $grupo->load(['leader', 'users']);

        $leader = $grupo->leader
            ? [
                'id' => $grupo->leader->id,
                'name' => $grupo->leader->name,
                'last_name' => $grupo->leader->last_name,
                'photo' => $grupo->leader->photo
            ]
            : null;

        $users = $grupo->users
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'last_name' => $u->last_name,
                'photo' => $u->photo,
                'is_leader' => (int) $grupo->leader_id === (int) $u->id,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'grupo' => $grupo->only(['id', 'name', 'description', 'leader_id']),
            'leader' => $leader,
            'users' => $users,
        ]);
    }
}
