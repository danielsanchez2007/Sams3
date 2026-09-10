<?php

namespace App\Http\Controllers;

use App\Models\ClaseEquipo;
use App\Models\Equipo;
use App\Services\HojaVidaAutoFields;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class FormatosController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureCanViewFormatos();
        $empresaId = $this->resolveTenantEmpresaId();

        $clases = ClaseEquipo::query()
            ->with('tipoEquipo')
            ->withCount(['equipos as equipos_activos_count' => fn ($q) => $q->where('activo', true)])
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->orderBy('nombre')
            ->get(['id', 'tipo_equipo_id', 'nombre']);

        return view('admin.formatos.index', compact('clases'));
    }

    public function clase(ClaseEquipo $clase)
    {
        $this->ensureCanViewFormatos();
        $this->ensureClaseTenant($clase);

        $equipos = Equipo::query()
            ->with(['empresa', 'sede', 'bodega', 'imagenes'])
            ->where('activo', true)
            ->where('clase_equipo_id', $clase->id)
            ->orderByRaw("CASE WHEN codigo LIKE 'IN-%' THEN CAST(SUBSTRING(codigo,4) AS UNSIGNED) END ASC")
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.formatos.clase', compact('clase', 'equipos'));
    }

    public function show(ClaseEquipo $clase, Equipo $equipo)
    {
        $this->ensureCanViewFormatos();
        $this->ensureEquipoClase($clase, $equipo);

        return view('admin.formatos.show', compact('clase', 'equipo'));
    }

    public function html(Request $request, ClaseEquipo $clase, Equipo $equipo)
    {
        $this->ensureCanViewFormatos();
        $this->ensureEquipoClase($clase, $equipo);

        $html = HojaVidaAutoFields::renderHtml($equipo, false, true);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
    }

    public function pdf(ClaseEquipo $clase, Equipo $equipo)
    {
        $this->ensureCanViewFormatos();
        $this->ensureEquipoClase($clase, $equipo);

        $html = HojaVidaAutoFields::renderHtml($equipo, true, true);
        $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false])
            ->loadHTML($html)
            ->setPaper('a4');

        $filename = 'hoja-vida-' . ($equipo->codigo ?: $equipo->id) . '.pdf';

        return $pdf->download($filename);
    }

    public function store()
    {
        $this->ensureCanViewFormatos();

        return redirect()->route('formatos.index')
            ->with('info', 'El formato de hoja de vida lo genera el sistema con los datos del equipo. Ya no se sube un Excel.');
    }

    public function download()
    {
        $this->ensureCanViewFormatos();

        return redirect()->route('formatos.index')
            ->with('info', 'El formato de hoja de vida lo genera el sistema. Abre una clase y un equipo para verlo o descargar el PDF.');
    }

    public function formReemplazar()
    {
        return $this->download();
    }

    public function reemplazar()
    {
        return $this->store();
    }

    private function ensureCanViewFormatos(): void
    {
        $authz = $this->moduleAuthz();
        if ($authz->canViewModule('hoja_vida') || $authz->canViewModule('inspeccion') || $authz->canViewModule('exportar')) {
            return;
        }

        abort(403, 'No tienes permiso para acceder a este módulo.');
    }

    private function ensureClaseTenant(ClaseEquipo $clase): void
    {
        if ($clase->empresa_id) {
            $this->moduleAuthz()->assertTenantOwns((int) $clase->empresa_id);
        }
    }

    private function ensureEquipoClase(ClaseEquipo $clase, Equipo $equipo): void
    {
        $this->ensureClaseTenant($clase);
        abort_unless($equipo->activo, 404);
        abort_unless((int) $equipo->clase_equipo_id === (int) $clase->id, 404);
        if ($equipo->empresa_id) {
            $this->moduleAuthz()->assertTenantOwns((int) $equipo->empresa_id);
        }
    }
}
