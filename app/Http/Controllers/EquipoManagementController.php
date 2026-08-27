<?php

namespace App\Http\Controllers;

use App\Models\ClaseEquipo;
use App\Models\Empresa;
use App\Models\TipoEquipo;
use App\Services\EmpresaContext;
use App\Services\VistaOficina;
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
        // Aislamiento estricto por empresa (no se mezclan datos de la matriz).
        $tiposQuery->where('empresa_id', $empresaId);

        // En modo Oficina (PW), solo se muestran los tipos/clases del apartado Oficina.
        // Convención: nombre comienza por "Oficina - ".
        if (VistaOficina::mostrarMenuOficina(auth()->user())) {
            $tiposQuery->where('nombre', 'like', 'Oficina - %');
        }

        $tiposEquipos = $tiposQuery->orderBy('nombre')->get();

        return view('admin.equipos.gestion', compact('tiposEquipos'));
    }

    public function storeTipo(Request $request)
    {
        $this->assertCanEditModule('equipos');
        $empresaId = $this->empresaActivaId();
        $isOficina = VistaOficina::mostrarMenuOficina(auth()->user());

        if ($isOficina) {
            $nombre = trim((string) $request->input('nombre', ''));
            if ($nombre !== '' && !str_starts_with(mb_strtolower($nombre), 'oficina - ')) {
                $request->merge(['nombre' => 'Oficina - ' . $nombre]);
            }
        }
        try {
            $request->validate([
                'nombre' => ['required', 'string', 'max:100', Rule::unique('tipo_equipos', 'nombre')->where('empresa_id', $empresaId)],
                'alias' => ['nullable', 'string', 'max:80'],
                'descripcion' => 'nullable|string'
            ]);

            TipoEquipo::create([
                'empresa_id' => $empresaId,
                'nombre' => $request->nombre,
                'alias' => $request->alias,
                'descripcion' => $request->descripcion,
                'activo' => true
            ]);

            return redirect()->route('equipos.gestion')->with('success', '✅ Tipo de equipo creado exitosamente');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return redirect()->back()
                ->with('error', 'No se pudo crear el tipo.')
                ->withInput();
        }
    }

    public function storeClase(Request $request)
    {
        $this->assertCanEditModule('equipos');
        $empresaId = $this->empresaActivaId();
        $isOficina = VistaOficina::mostrarMenuOficina(auth()->user());
        try {
            $request->validate([
                'tipo_equipo_id' => ['required', Rule::exists('tipo_equipos', 'id')->where('empresa_id', $empresaId)],
                'nombre' => 'required|string|max:100',
                'alias' => ['nullable', 'string', 'max:80'],
                'descripcion' => 'nullable|string'
            ]);

            if ($isOficina) {
                $tipo = TipoEquipo::query()->where('empresa_id', $empresaId)->whereKey((int) $request->input('tipo_equipo_id'))->first();
                if (!$tipo || !str_starts_with(mb_strtolower((string) $tipo->nombre), 'oficina - ')) {
                    return redirect()->back()->with('error', 'En modo Oficina solo puedes crear clases dentro de tipos "Oficina - ...".')->withInput();
                }
            }

            ClaseEquipo::create([
                'empresa_id' => $empresaId,
                'tipo_equipo_id' => $request->tipo_equipo_id,
                'nombre' => $request->nombre,
                'alias' => $request->alias,
                'descripcion' => $request->descripcion,
                'activo' => true
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

    public function editTipo($id)
    {
        $this->assertCanViewModule('equipos');
        $empresaId = $this->empresaActivaId();
        $tipo = TipoEquipo::where('empresa_id', $empresaId)->findOrFail($id);
        if (VistaOficina::mostrarMenuOficina(auth()->user()) && !str_starts_with(mb_strtolower((string) $tipo->nombre), 'oficina - ')) {
            abort(404);
        }
        return view('admin.equipos.edit-tipo', compact('tipo'));
    }

    public function editClase($id)
    {
        $this->assertCanViewModule('equipos');
        $empresaId = $this->empresaActivaId();
        $clase = ClaseEquipo::with('tipoEquipo')->where('empresa_id', $empresaId)->findOrFail($id);
        if (VistaOficina::mostrarMenuOficina(auth()->user()) && !str_starts_with(mb_strtolower((string) ($clase->tipoEquipo?->nombre ?? '')), 'oficina - ')) {
            abort(404);
        }
        $tiposEquipos = TipoEquipo::where('empresa_id', $empresaId)
            ->when(VistaOficina::mostrarMenuOficina(auth()->user()), fn ($q) => $q->where('nombre', 'like', 'Oficina - %'))
            ->orderBy('nombre')
            ->get();
        return view('admin.equipos.edit-clase', compact('clase', 'tiposEquipos'));
    }

    public function updateTipo(Request $request, $id)
    {
        $this->assertCanEditModule('equipos');
        $empresaId = $this->empresaActivaId();
        try {
            $tipo = TipoEquipo::where('empresa_id', $empresaId)->findOrFail($id);
            $isOficina = VistaOficina::mostrarMenuOficina(auth()->user());
            if ($isOficina && !str_starts_with(mb_strtolower((string) $tipo->nombre), 'oficina - ')) {
                abort(404);
            }

            if ($isOficina) {
                $nombre = trim((string) $request->input('nombre', ''));
                if ($nombre !== '' && !str_starts_with(mb_strtolower($nombre), 'oficina - ')) {
                    $request->merge(['nombre' => 'Oficina - ' . $nombre]);
                }
            }
            
            $request->validate([
                'nombre' => ['required', 'string', 'max:100', Rule::unique('tipo_equipos', 'nombre')->where('empresa_id', $empresaId)->ignore($id)],
                'alias' => ['nullable', 'string', 'max:80'],
                'descripcion' => 'nullable|string'
            ]);

            $tipo->update([
                'nombre' => $request->nombre,
                'alias' => $request->alias,
                'descripcion' => $request->descripcion
            ]);

            return redirect()->route('equipos.gestion')->with('success', '✅ Tipo de equipo actualizado exitosamente');
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
            if (VistaOficina::mostrarMenuOficina(auth()->user())) {
                $tipo = TipoEquipo::query()->where('empresa_id', $empresaId)->whereKey((int) $request->input('tipo_equipo_id'))->first();
                if (!$tipo || !str_starts_with(mb_strtolower((string) $tipo->nombre), 'oficina - ')) {
                    return redirect()->back()->with('error', 'En modo Oficina solo puedes mover/editar clases dentro de tipos "Oficina - ...".')->withInput();
                }
            }

            $request->validate([
                'tipo_equipo_id' => ['required', Rule::exists('tipo_equipos', 'id')->where('empresa_id', $empresaId)],
                'nombre' => 'required|string|max:100',
                'alias' => ['nullable', 'string', 'max:80'],
                'descripcion' => 'nullable|string'
            ]);

            $clase->update([
                'tipo_equipo_id' => $request->tipo_equipo_id,
                'nombre' => $request->nombre,
                'alias' => $request->alias,
                'descripcion' => $request->descripcion
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

    public function deleteTipo($id)
    {
        $this->assertCanEditModule('equipos');
        $empresaId = $this->empresaActivaId();
        try {
            $tipo = TipoEquipo::where('empresa_id', $empresaId)->findOrFail($id);
            if (VistaOficina::mostrarMenuOficina(auth()->user()) && !str_starts_with(mb_strtolower((string) $tipo->nombre), 'oficina - ')) {
                abort(404);
            }
            $tipo->delete();

            return response()->json([
                'success' => true,
                'message' => '🗑️ Tipo de equipo eliminado exitosamente.'
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo eliminar el tipo.'
            ], 422);
        }
    }

    public function deleteClase($id)
    {
        $this->assertCanEditModule('equipos');
        $empresaId = $this->empresaActivaId();
        try {
            $clase = ClaseEquipo::with('tipoEquipo')->where('empresa_id', $empresaId)->findOrFail($id);
            if (VistaOficina::mostrarMenuOficina(auth()->user()) && !str_starts_with(mb_strtolower((string) ($clase->tipoEquipo?->nombre ?? '')), 'oficina - ')) {
                abort(404);
            }
            $clase->delete();

            return response()->json([
                'success' => true,
                'message' => '🗑️ Clase de equipo eliminada exitosamente.'
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo eliminar la clase.'
            ], 422);
        }
    }

    public function toggleTipoStatus($id)
    {
        $this->assertCanEditModule('equipos');
        $empresaId = $this->empresaActivaId();
        try {
            $tipo = TipoEquipo::where('empresa_id', $empresaId)->findOrFail($id);
            if (VistaOficina::mostrarMenuOficina(auth()->user()) && !str_starts_with(mb_strtolower((string) $tipo->nombre), 'oficina - ')) {
                abort(404);
            }
            $tipo->update(['activo' => !$tipo->activo]);

            $status = $tipo->activo ? 'activado' : 'desactivado';
            return response()->json([
                'success' => true,
                'message' => "✅ Tipo de equipo {$status} exitosamente."
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo cambiar el estado.'
            ], 422);
        }
    }

    public function toggleClaseStatus($id)
    {
        $this->assertCanEditModule('equipos');
        $empresaId = $this->empresaActivaId();
        try {
            $clase = ClaseEquipo::with('tipoEquipo')->where('empresa_id', $empresaId)->findOrFail($id);
            if (VistaOficina::mostrarMenuOficina(auth()->user()) && !str_starts_with(mb_strtolower((string) ($clase->tipoEquipo?->nombre ?? '')), 'oficina - ')) {
                abort(404);
            }
            $clase->update(['activo' => !$clase->activo]);

            $status = $clase->activo ? 'activada' : 'desactivada';
            return response()->json([
                'success' => true,
                'message' => "✅ Clase de equipo {$status} exitosamente."
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo cambiar el estado.'
            ], 422);
        }
    }
}
