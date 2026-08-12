<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\EquipoAuditoriaTraspaso;
use App\Services\CodigoEquipoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EquiposAuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAuditoria', Equipo::class);

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

        $traspasos = EquipoAuditoriaTraspaso::query()
            ->with(['equipo.imagenes', 'creador'])
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.equipos.auditoria.index', compact('equiposJs', 'traspasos'));
    }

    public function traspasar(Request $request)
    {
        $request->validate([
            'equipo_id' => ['required', 'integer', 'exists:equipos,id'],
        ]);

        $equipo = Equipo::query()->with('tipoEquipo')->whereKey($request->integer('equipo_id'))->firstOrFail();
        $this->authorize('traspasar', [$equipo, 'auditoria']);
        abort_unless($equipo->activo, 404);

        $codigoActual = (string) $equipo->codigo;
        if (CodigoEquipoService::esAuditoria($codigoActual)) {
            return redirect()->route('equipos.auditoria.index')->with('error', '⚠️ Ese equipo ya está en auditoría.');
        }

        return DB::transaction(function () use ($equipo) {
            $codigoAnterior = (string) $equipo->codigo;
            $codigoAud = CodigoEquipoService::nextCodigoAuditoria($equipo);

            EquipoAuditoriaTraspaso::create([
                'equipo_id' => $equipo->id,
                'codigo_aud' => $codigoAud,
                'codigo_anterior' => $codigoAnterior,
                'traspasado_por' => auth()->id(),
                'traspasado_en' => now(),
            ]);

            $equipo->update([
                'codigo' => $codigoAud,
                'serial' => $codigoAud,
            ]);

            CodigoEquipoService::liberarInventario($codigoAnterior);

            return redirect()->route('equipos.auditoria.index')->with('success', "✅ Equipo traspasado a auditoría ({$codigoAud})");
        });
    }
}
