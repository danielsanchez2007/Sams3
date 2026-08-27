<?php

namespace App\Http\Controllers;

use App\Models\ClaseEquipo;
use App\Models\TipoEquipo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class ClaseEquipoController extends Controller
{
    public function index()
    {
        return redirect()->route('equipos.gestion');
    }

    public function store(Request $request)
    {
        $this->assertCanEditModule('equipos');
        $empresaId = $this->assertTenantEmpresaId();

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
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return redirect()->back()
                ->with('error', 'No se pudo crear la clase.')
                ->withInput();
        }
    }

    public function edit(ClaseEquipo $claseEquipo)
    {
        $this->assertCanViewModule('equipos');
        $empresaId = $this->assertTenantEmpresaId();
        if ((int) $claseEquipo->empresa_id !== $empresaId) {
            abort(403);
        }
        $tiposEquipos = TipoEquipo::activos()->where('empresa_id', $empresaId)->orderBy('nombre')->get();
        $clase = $claseEquipo;

        return view('admin.equipos.edit-clase', compact('clase', 'tiposEquipos'));
    }

    public function update(Request $request, ClaseEquipo $claseEquipo)
    {
        $this->assertCanEditModule('equipos');
        $empresaId = $this->assertTenantEmpresaId();
        if ((int) $claseEquipo->empresa_id !== $empresaId) {
            abort(403);
        }

        try {
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
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return redirect()->back()
                ->with('error', 'No se pudo actualizar la clase.')
                ->withInput();
        }
    }

    public function destroy(ClaseEquipo $claseEquipo)
    {
        $this->assertCanEditModule('equipos');
        $empresaId = $this->assertTenantEmpresaId();
        if ((int) $claseEquipo->empresa_id !== $empresaId) {
            abort(403);
        }
        $claseEquipo->delete();

        return response()->json([
            'success' => true,
            'message' => '🗑️ Clase de equipo eliminada exitosamente.',
        ]);
    }

    public function toggleStatus(ClaseEquipo $claseEquipo)
    {
        $this->assertCanEditModule('equipos');
        $empresaId = $this->assertTenantEmpresaId();
        if ((int) $claseEquipo->empresa_id !== $empresaId) {
            abort(403);
        }
        $claseEquipo->update(['activo' => !$claseEquipo->activo]);

        $status = $claseEquipo->activo ? 'activada' : 'desactivada';

        return response()->json([
            'success' => true,
            'message' => "✅ Clase de equipo {$status} exitosamente.",
        ]);
    }
}
