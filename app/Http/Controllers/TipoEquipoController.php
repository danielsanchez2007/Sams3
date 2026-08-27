<?php

namespace App\Http\Controllers;

use App\Models\TipoEquipo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TipoEquipoController extends Controller
{
    public function index()
    {
        return redirect()->route('equipos.gestion');
    }

    private function resolveEmpresaId(): int
    {
        return $this->assertTenantEmpresaId();
    }

    public function store(Request $request)
    {
        $this->assertCanEditModule('equipos');
        $empresaId = $this->resolveEmpresaId();
        if (!$empresaId) {
            return redirect()->route('equipos.gestion')->with('error', 'Debe estar en una empresa para crear tipos.');
        }
        $request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('tipo_equipos', 'nombre')->where('empresa_id', $empresaId)],
            'alias' => ['nullable', 'string', 'max:80'],
            'descripcion' => 'nullable|string',
        ]);

        TipoEquipo::create([
            'empresa_id' => $empresaId,
            'nombre' => $request->nombre,
            'alias' => $request->filled('alias') ? trim((string) $request->alias) : null,
            'descripcion' => $request->descripcion,
            'activo' => $request->boolean('activo', true),
        ]);

        return redirect()->route('equipos.gestion')->with('success', '✅ Tipo de equipo creado exitosamente');
    }

    public function edit(TipoEquipo $tipoEquipo)
    {
        $this->assertCanViewModule('equipos');
        $empresaId = $this->resolveEmpresaId();
        if ($tipoEquipo->empresa_id !== $empresaId) {
            abort(403);
        }
        return view('admin.equipos.edit-tipo', ['tipo' => $tipoEquipo]);
    }

    public function update(Request $request, TipoEquipo $tipoEquipo)
    {
        $this->assertCanEditModule('equipos');
        $empresaId = $this->resolveEmpresaId();
        if ($tipoEquipo->empresa_id !== $empresaId) {
            abort(403);
        }
        $request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('tipo_equipos', 'nombre')->where('empresa_id', $empresaId)->ignore($tipoEquipo->id)],
            'alias' => ['nullable', 'string', 'max:80'],
            'descripcion' => 'nullable|string',
        ]);

        $tipoEquipo->update([
            'nombre' => $request->nombre,
            'alias' => $request->filled('alias') ? trim((string) $request->alias) : null,
            'descripcion' => $request->descripcion,
        ]);

        return redirect()->route('equipos.gestion')->with('success', '✅ Tipo de equipo actualizado exitosamente');
    }

    public function destroy(TipoEquipo $tipoEquipo)
    {
        $this->assertCanEditModule('equipos');
        $empresaId = $this->resolveEmpresaId();
        if ($tipoEquipo->empresa_id !== $empresaId) {
            abort(403);
        }
        $tipoEquipo->delete();
        
        return response()->json([
            'success' => true,
            'message' => '🗑️ Tipo de equipo eliminado exitosamente.'
        ]);
    }

    public function toggleStatus(TipoEquipo $tipoEquipo)
    {
        $this->assertCanEditModule('equipos');
        $empresaId = $this->resolveEmpresaId();
        if ($tipoEquipo->empresa_id !== $empresaId) {
            abort(403);
        }
        $tipoEquipo->update(['activo' => !$tipoEquipo->activo]);
        
        $status = $tipoEquipo->activo ? 'activado' : 'desactivado';
        return response()->json([
            'success' => true,
            'message' => "✅ Tipo de equipo {$status} exitosamente."
        ]);
    }
}
