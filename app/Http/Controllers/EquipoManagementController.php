<?php

namespace App\Http\Controllers;

use App\Models\ClaseEquipo;
use App\Models\TipoEquipo;
use App\Services\EmpresaContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class EquipoManagementController extends Controller
{
    private function empresaActivaId(): int
    {
        $empresaId = EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;
        if (!$empresaId) {
            abort(403, 'Debes seleccionar una empresa activa para gestionar tipos y clases.');
        }

        return (int) $empresaId;
    }

    public function index()
    {
        $this->assertCanViewModule('equipos');
        $empresaId = $this->empresaActivaId();

        $tiposQuery = TipoEquipo::with([
            'clases' => static function ($q) {
                $q->orderBy('nombre')->withCount('equipos');
            },
        ]);
        $tiposQuery->where('empresa_id', $empresaId);

        $tiposEquipos = $tiposQuery->orderBy('nombre')->get();

        return view('admin.equipos.gestion', compact('tiposEquipos'));
    }

    public function updateTipo(Request $request, $id)
    {
        $this->assertCanEditModule('equipos');
        $empresaId = $this->empresaActivaId();
        try {
            $tipo = TipoEquipo::where('empresa_id', $empresaId)->findOrFail($id);

            $request->validate([
                'nombre' => ['required', 'string', 'max:100', Rule::unique('tipo_equipos', 'nombre')->where('empresa_id', $empresaId)->ignore($id)],
                'alias' => ['nullable', 'string', 'max:80'],
                'descripcion' => 'nullable|string',
            ]);

            $tipo->update([
                'nombre' => $request->nombre,
                'alias' => $request->alias,
                'descripcion' => $request->descripcion,
            ]);

            return redirect()->route('equipos.gestion')->with('success', 'Tipo de equipo actualizado exitosamente');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return redirect()->back()
                ->with('error', 'No se pudo actualizar el tipo.')
                ->withInput();
        }
    }

    public function updateClase(Request $request, $id)
    {
        $this->assertCanEditModule('equipos');
        $empresaId = $this->empresaActivaId();
        try {
            $clase = ClaseEquipo::where('empresa_id', $empresaId)->findOrFail($id);

            $request->validate([
                'tipo_equipo_id' => ['required', Rule::exists('tipo_equipos', 'id')->where('empresa_id', $empresaId)],
                'nombre' => 'required|string|max:100',
                'alias' => ['nullable', 'string', 'max:80'],
                'descripcion' => 'nullable|string',
            ]);

            $clase->update([
                'tipo_equipo_id' => $request->tipo_equipo_id,
                'nombre' => $request->nombre,
                'alias' => $request->alias,
                'descripcion' => $request->descripcion,
            ]);

            return redirect()->route('equipos.gestion')->with('success', 'Clase de equipo actualizada exitosamente');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return redirect()->back()
                ->with('error', 'No se pudo actualizar la clase.')
                ->withInput();
        }
    }
}
