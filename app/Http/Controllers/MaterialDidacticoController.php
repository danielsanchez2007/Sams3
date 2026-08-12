<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\MaterialDidacticoTraspaso;
use App\Models\TipoEquipo;
use App\Services\CodigoEquipoService;
use App\Services\EmpresaContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaterialDidacticoController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewMaterial', Equipo::class);

        // Solo equipos activos: si un equipo fue dado de baja ya no debe aparecer
        $equipos = Equipo::query()
            ->where('activo', true)
            ->with(['imagenes'])
            ->orderBy('id')
            ->limit(400)
            ->get();

        $equiposJs = $equipos->map(function ($e) {
            $imgEtiqueta = $e->imagenes->firstWhere('tipo', 'etiqueta');
            $imgGeneral = $e->imagenes->firstWhere('tipo', 'general');

            return [
                'id' => $e->id,
                'codigo' => $e->codigo,
                'nombre' => $e->nombre,
                'serial' => $e->serial,
                'descripcion' => $e->descripcion,
                'img' => ($imgEtiqueta?->path ?: $imgGeneral?->path) ? asset('storage/' . ($imgEtiqueta?->path ?: $imgGeneral?->path)) : null,
                'img_general' => $imgGeneral?->path ? asset('storage/' . $imgGeneral->path) : null,
                'img_etiqueta' => $imgEtiqueta?->path ? asset('storage/' . $imgEtiqueta->path) : null,
            ];
        })->values();

        $traspasos = MaterialDidacticoTraspaso::query()
            ->with(['equipo.imagenes', 'creador'])
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.equipos.material_didactico.index', compact('equiposJs', 'traspasos'));
    }

    public function traspasar(Request $request)
    {
        $request->validate([
            'equipo_id' => ['required', 'integer', 'exists:equipos,id'],
        ]);

        $equipo = Equipo::query()->whereKey($request->integer('equipo_id'))->firstOrFail();
        $this->authorize('traspasar', [$equipo, 'didactico']);
        abort_unless($equipo->activo, 404);

        $codigoActual = (string) $equipo->codigo;
        if (CodigoEquipoService::esMaterial($codigoActual)) {
            return redirect()->route('equipos.material-didactico.index')->with('error', '⚠️ Ese equipo ya está en material didáctico.');
        }

        $empresaId = $equipo->empresa_id ?? EmpresaContext::empresaId() ?? Empresa::orderBy('id')->value('id');
        $tipoDidacticoId = (int) TipoEquipo::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->where(function ($q) {
                $q->whereRaw('LOWER(nombre) LIKE ?', ['%didact%'])
                    ->orWhereRaw('LOWER(nombre) LIKE ?', ['%didáct%'])
                    ->orWhereRaw('LOWER(nombre) LIKE ?', ['%material%']);
            })
            ->orderBy('id')
            ->value('id');

        if ($tipoDidacticoId <= 0) {
            $tipo = TipoEquipo::query()->firstOrCreate(
                ['nombre' => 'Material Didáctico', 'empresa_id' => $empresaId],
                ['descripcion' => null, 'activo' => true]
            );
            $tipoDidacticoId = (int) $tipo->id;
        }

        return DB::transaction(function () use ($equipo, $tipoDidacticoId) {
            $codigoAnterior = (string) $equipo->codigo;
            $codigoMd = CodigoEquipoService::nextCodigoMaterial($equipo, $tipoDidacticoId);

            MaterialDidacticoTraspaso::create([
                'equipo_id' => $equipo->id,
                'codigo_md' => $codigoMd,
                'codigo_anterior' => $codigoAnterior,
                'traspasado_por' => auth()->id(),
                'traspasado_en' => now(),
            ]);

            $equipo->update([
                'codigo' => $codigoMd,
                'serial' => $codigoMd,
                'tipo_equipo_id' => $tipoDidacticoId,
            ]);

            CodigoEquipoService::liberarInventario($codigoAnterior);

            return redirect()->route('equipos.material-didactico.index')->with('success', "✅ Equipo traspasado a material didáctico ({$codigoMd})");
        });
    }
}

