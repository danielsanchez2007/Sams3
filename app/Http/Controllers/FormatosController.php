<?php

namespace App\Http\Controllers;

use App\Models\HojaVidaPlantilla;
use App\Models\ClaseEquipo;
use App\Models\Equipo;
use App\Models\HojaVidaDocumento;
use App\Support\UploadedFileStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FormatosController extends Controller
{
    public function index(Request $request)
    {
        $this->assertCanViewModule('hoja_vida');
        $empresaId = \App\Services\EmpresaContext::empresaId() ?? auth()->user()?->empresa_id;
        $clases = ClaseEquipo::query()
            ->with('tipoEquipo')
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->orderBy('nombre')
            ->get(['id', 'tipo_equipo_id', 'nombre']);

        $plantillas = HojaVidaPlantilla::query()
            ->whereNotNull('clase_equipo_id')
            ->when($empresaId, fn ($q) => $q->whereHas('claseEquipo', fn ($c) => $c->where('empresa_id', $empresaId)))
            ->with(['tipoEquipo', 'claseEquipo'])
            ->orderByDesc('id')
            ->get();

        return view('admin.formatos.index', compact('clases', 'plantillas'));
    }

    public function store(Request $request)
    {
        $this->assertCanEditModule('hoja_vida');
        $request->validate([
            'clase_equipo_id' => ['required', 'integer', 'exists:clase_equipos,id'],
            'plantilla_excel' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $empresaId = \App\Services\EmpresaContext::empresaId() ?? auth()->user()?->empresa_id;
        $clase = ClaseEquipo::query()->with('tipoEquipo')->whereKey($request->integer('clase_equipo_id'))->firstOrFail();

        if ($empresaId && (int) $clase->empresa_id !== (int) $empresaId) {
            abort(403, 'No puedes asignar formato a una clase de otra empresa.');
        }

        $file = $request->file('plantilla_excel');
        try {
            $path = UploadedFileStorage::storePublicSpreadsheet($file, 'hoja_vida/plantillas');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('formatos.index')
                ->with('error', 'No se pudo guardar la plantilla.');
        }

        HojaVidaPlantilla::query()->updateOrCreate(
            ['clase_equipo_id' => $clase->id],
            [
                'tipo_equipo_id' => $clase->tipo_equipo_id,
                'plantilla_excel_path' => $path,
                'creado_por' => auth()->id(),
            ]
        );

        return redirect()->route('formatos.index')->with('success', '✅ Plantilla Excel asignada correctamente a la Clase de Equipo');
    }

    public function download(HojaVidaPlantilla $plantilla)
    {
        $this->assertCanViewModule('hoja_vida');
        $this->ensurePlantillaBelongsToEmpresa($plantilla);
        if (!$plantilla->plantilla_excel_path || !Storage::disk('public')->exists($plantilla->plantilla_excel_path)) {
            abort(404);
        }

        $filename = 'plantilla-hoja-vida-clase-' . ($plantilla->clase_equipo_id ?: $plantilla->tipo_equipo_id) . '.xlsx';

        return Storage::disk('public')->download($plantilla->plantilla_excel_path, $filename);
    }

    public function formReemplazar(HojaVidaPlantilla $plantilla)
    {
        $this->assertCanEditModule('hoja_vida');
        $this->ensurePlantillaBelongsToEmpresa($plantilla);
        $plantilla->load(['claseEquipo', 'tipoEquipo']);
        return view('admin.formatos.reemplazar', compact('plantilla'));
    }

    public function reemplazar(Request $request, HojaVidaPlantilla $plantilla)
    {
        $this->assertCanEditModule('hoja_vida');
        $this->ensurePlantillaBelongsToEmpresa($plantilla);
        $request->validate([
            'plantilla_excel' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        if ($plantilla->plantilla_excel_path && Storage::disk('public')->exists($plantilla->plantilla_excel_path)) {
            Storage::disk('public')->delete($plantilla->plantilla_excel_path);
        }

        $file = $request->file('plantilla_excel');
        try {
            $path = UploadedFileStorage::storePublicSpreadsheet($file, 'hoja_vida/plantillas');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('formatos.index')
                ->with('error', 'No se pudo guardar la plantilla.');
        }

        $plantilla->update(['plantilla_excel_path' => $path]);

        // Al reemplazar el formato, se borran las hojas de vida ya guardadas de todos los equipos
        // de esta clase para que tengan que rellenar de nuevo con la nueva plantilla.
        $equipoIds = Equipo::where('clase_equipo_id', $plantilla->clase_equipo_id)->pluck('id');
        HojaVidaDocumento::whereIn('equipo_id', $equipoIds)->delete();

        return redirect()->route('formatos.index')->with('success', '✅ Formato reemplazado. Los equipos de esta clase deberán rellenar la hoja de vida de nuevo.');
    }

    private function ensurePlantillaBelongsToEmpresa(HojaVidaPlantilla $plantilla): void
    {
        $plantilla->loadMissing('claseEquipo');
        $empresaId = $this->resolveTenantEmpresaId();
        if (!$empresaId) {
            if (!$this->moduleAuthz()->isGlobalAdmin()) {
                abort(403, 'No tienes acceso a esta plantilla.');
            }

            return;
        }
        if (!$plantilla->claseEquipo || (int) $plantilla->claseEquipo->empresa_id !== (int) $empresaId) {
            abort(403, 'No tienes acceso a esta plantilla.');
        }
    }
}
