<?php

namespace App\Http\Controllers;

use App\Models\ClaseEquipo;
use App\Models\Empresa;
use App\Models\TipoEquipo;
use App\Services\EmpresaContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClaseEquipoController extends Controller
{
    public function index()
    {
        return redirect()->route('equipos.gestion');
    }

    public function store(Request $request)
    {
        $empresaId = EmpresaContext::empresaId() ?? Empresa::orderBy('id')->value('id');
        if (!$empresaId) {
            return redirect()->route('equipos.gestion')->with('error', 'Debe estar en una empresa para crear clases.');
        }
        try {
            $request->validate([
                'tipo_equipo_id' => ['required', Rule::exists('tipo_equipos', 'id')->where('empresa_id', $empresaId)],
                'nombre' => 'required|string|max:100',
                'alias' => ['nullable', 'string', 'max:80'],
                'descripcion' => 'nullable|string',
            ]);

            ClaseEquipo::create([
                'empresa_id' => $empresaId,
                'tipo_equipo_id' => $request->tipo_equipo_id,
                'nombre' => $request->nombre,
                'alias' => $request->filled('alias') ? trim((string) $request->alias) : null,
                'descripcion' => $request->descripcion,
                'activo' => $request->boolean('activo', true),
            ]);

            return redirect()->route('equipos.gestion')->with('success', '✅ Clase de equipo creada exitosamente');
            
        } catch (\Exception $e) {
            \Log::error('Error creating ClaseEquipo: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', '❌ Error al crear la clase: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function edit(ClaseEquipo $claseEquipo)
    {
        $tiposEquipos = TipoEquipo::activos()->get();
        $clase = $claseEquipo;
        return view('admin.equipos.edit-clase', compact('clase', 'tiposEquipos'));
    }

    public function update(Request $request, ClaseEquipo $claseEquipo)
    {
        try {
            // Validar datos básicos
            $request->validate([
                'tipo_equipo_id' => ['required', Rule::exists('tipo_equipos', 'id')->where('empresa_id', $claseEquipo->empresa_id)],
                'nombre' => 'required|string|max:100',
                'alias' => ['nullable', 'string', 'max:80'],
                'descripcion' => 'nullable|string',
            ]);

            $claseEquipo->update([
                'tipo_equipo_id' => $request->tipo_equipo_id,
                'nombre' => $request->nombre,
                'alias' => $request->filled('alias') ? trim((string) $request->alias) : null,
                'descripcion' => $request->descripcion,
                'activo' => $request->boolean('activo', true),
            ]);

            return redirect()->route('equipos.gestion')->with('success', '✅ Clase de equipo actualizada exitosamente');
            
        } catch (\Exception $e) {
            \Log::error('Error updating ClaseEquipo: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', '❌ Error al actualizar la clase: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(ClaseEquipo $claseEquipo)
    {
        $empresaId = EmpresaContext::empresaId() ?? Empresa::orderBy('id')->value('id');
        if ($claseEquipo->empresa_id !== $empresaId) {
            abort(403);
        }
        $claseEquipo->delete();
        
        return response()->json([
            'success' => true,
            'message' => '🗑️ Clase de equipo eliminada exitosamente.'
        ]);
    }

    public function toggleStatus(ClaseEquipo $claseEquipo)
    {
        $empresaId = EmpresaContext::empresaId() ?? Empresa::orderBy('id')->value('id');
        if ($claseEquipo->empresa_id !== $empresaId) {
            abort(403);
        }
        $claseEquipo->update(['activo' => !$claseEquipo->activo]);
        
        $status = $claseEquipo->activo ? 'activada' : 'desactivada';
        return response()->json([
            'success' => true,
            'message' => "✅ Clase de equipo {$status} exitosamente."
        ]);
    }
}
