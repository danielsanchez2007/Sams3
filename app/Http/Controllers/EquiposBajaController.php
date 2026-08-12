<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBajaRequest;
use App\Http\Requests\UpdateBajaRequest;
use App\Models\AppSetting;
use App\Models\CodigoReutilizable;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\EquipoBaja;
use App\Models\EquipoInspeccion;
use App\Models\User;
use App\Services\CodigoEquipoService;
use App\Services\EmpresaContext;
use App\Support\EquipoDetalle;
use App\Support\HtmlSanitizer;
use App\Support\UploadedFileStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Html as SpreadsheetHtmlWriter;

class EquiposBajaController extends Controller
{
    private const SETTING_PLANTILLA_EXCEL = 'bajas.plantilla_excel_path';

    private const SETTING_PLANTILLA_HTML = 'bajas.plantilla_html_path.v2';

    private function plantillaExcelKey(): string
    {
        $empresaId = EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;
        return $empresaId ? self::SETTING_PLANTILLA_EXCEL . '.' . $empresaId : self::SETTING_PLANTILLA_EXCEL;
    }

    private function plantillaHtmlKey(): string
    {
        $empresaId = EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;
        return $empresaId ? self::SETTING_PLANTILLA_HTML . '.' . $empresaId : self::SETTING_PLANTILLA_HTML;
    }

    public function index(Request $request)
    {
        $this->authorize('viewBajas', Equipo::class);

        $empresaId = EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;
        $plantillaExcelPath = AppSetting::getValue($this->plantillaExcelKey());
        $patronCodigo = $empresaId ? EmpresaContext::prefijo() . '-IN-%' : 'IN-%';

        $todosEquipos = Equipo::query()
            ->when($empresaId, fn ($q) => $q->where(fn ($sub) => $sub->where('empresa_id', $empresaId)->orWhereNull('empresa_id')))
            ->with(['imagenes:id,equipo_id,tipo,path'])
            ->orderByRaw("CAST(COALESCE(NULLIF(SUBSTRING_INDEX(codigo,'-',-1),''), SUBSTRING(codigo,4)) AS UNSIGNED) ASC")
            ->orderBy('id')
            ->get(['id', 'codigo', 'nombre', 'serial', 'activo']);

        $equiposJs = $todosEquipos
            ->where('activo', true)
            ->map(fn ($e) => $this->mapEquipoForJs($e))
            ->values();

        $equiposEliminarJs = $todosEquipos
            ->map(fn ($e) => $this->mapEquipoForJs($e))
            ->values();

        $bajas = EquipoBaja::query()
            ->with(['equipo.imagenes'])
            ->when($empresaId, fn ($q) => $q->whereHas('equipo', fn ($eq) => $eq->where(fn ($sub) => $sub->where('empresa_id', $empresaId)->orWhereNull('empresa_id'))))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $plantillaHtmlPath = AppSetting::getValue($this->plantillaHtmlKey());
        $tieneFormatoHtml = $plantillaHtmlPath && Storage::disk('public')->exists($plantillaHtmlPath);
        $zipDisponible = $this->soportaLecturaExcel();

        return view('admin.equipos.bajas.index', compact('plantillaExcelPath', 'plantillaHtmlPath', 'tieneFormatoHtml', 'equiposJs', 'bajas', 'equiposEliminarJs', 'zipDisponible'));
    }

    public function uploadPlantilla(Request $request)
    {
        $this->authorize('viewBajas', Equipo::class);

        $request->validate([
            'plantilla_excel' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $file = $request->file('plantilla_excel');

        try {
            $path = UploadedFileStorage::storePublicSpreadsheet($file, 'bajas/plantillas');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('equipos.bajas.index')
                ->with('error', 'No se pudo guardar el formato: ' . $e->getMessage());
        }

        AppSetting::setValue($this->plantillaExcelKey(), $path);
        AppSetting::setValue($this->plantillaHtmlKey(), null);

        if (!$this->soportaLecturaExcel()) {
            return redirect()->route('equipos.bajas.index')
                ->with('error', 'âš ï¸ El formato se guardÃ³, pero PHP no tiene habilitada la extensiÃ³n ZIP, necesaria para leer archivos Excel. Mientras tanto el sistema usarÃ¡ el acta PDF profesional integrada. Active extension=zip en php.ini y reinicie el servidor.');
        }

        return redirect()->route('equipos.bajas.index')->with('success', 'âœ… Formato de bajas cargado correctamente.');
    }

    /** PhpSpreadsheet necesita ZipArchive para abrir archivos .xlsx. */
    private function soportaLecturaExcel(): bool
    {
        return class_exists(\ZipArchive::class);
    }

    public function regenerarPlantillaHtml(Request $request)
    {
        $this->authorize('viewBajas', Equipo::class);

        $plantillaExcelPath = AppSetting::getValue($this->plantillaExcelKey());
        if (!$plantillaExcelPath || !Storage::disk('public')->exists($plantillaExcelPath)) {
            return redirect()->route('equipos.bajas.index')->with('error', 'No hay plantilla Excel cargada.');
        }

        if (!$this->soportaLecturaExcel()) {
            return redirect()->route('equipos.bajas.index')
                ->with('error', 'âš ï¸ PHP no tiene habilitada la extensiÃ³n ZIP, necesaria para leer archivos Excel. Active extension=zip en php.ini y reinicie el servidor.');
        }

        $htmlPath = $this->cachePlantillaHtmlSimple(Storage::disk('public')->path($plantillaExcelPath));
        if ($htmlPath) {
            AppSetting::setValue($this->plantillaHtmlKey(), $htmlPath);
            return redirect()->route('equipos.bajas.index')->with('success', 'Formato actualizado.');
        }
        return redirect()->route('equipos.bajas.index')->with('error', 'No se pudo procesar el archivo Excel.');
    }

    public function form(Request $request, Equipo $equipo)
    {
        $this->authorize('darDeBaja', $equipo);
        abort_unless($equipo->activo, 404);
        EquipoDetalle::loadCompleto($equipo);

        $plantillaExcelPath = AppSetting::getValue($this->plantillaExcelKey());
        $tienePlantillaExcel = $plantillaExcelPath
            && Storage::disk('public')->exists($plantillaExcelPath)
            && $this->soportaLecturaExcel();

        $inspeccionId = $request->query('inspeccion_id');
        $inspeccion = null;
        if ($inspeccionId && ctype_digit((string) $inspeccionId)) {
            $inspeccion = EquipoInspeccion::query()
                ->where('id', (int) $inspeccionId)
                ->where('equipo_id', $equipo->id)
                ->first();
        }

        $defaults = array_merge(
            EquipoDetalle::autoFields($equipo, null),
            [
                'FECHA_BAJA' => now()->format('Y-m-d'),
                'MOTIVO_BAJA' => '',
                'OBSERVACIONES_BAJA' => '',
                'RESUMEN_BAJA' => '',
            ]
        );

        if ($tienePlantillaExcel) {
            $templateHtml = $this->getPlantillaHtml(Storage::disk('public')->path($plantillaExcelPath));
            $renderedHtml = $this->replaceTokens($templateHtml, $defaults);
            $renderedHtml = $this->blankRemainingTokens($renderedHtml);
        } else {
            $renderedHtml = $this->buildActaPreviewHtml($equipo, null, $defaults);
            $plantillaExcelPath = null;
        }

        $empresaIdUsuarios = EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;

        $usuarios = User::query()
            ->where('active', true)
            ->when($empresaIdUsuarios, fn ($q) => $q->where(fn ($sub) => $sub->where('empresa_id', $empresaIdUsuarios)->orWhereNull('empresa_id')))
            ->orderBy('name')
            ->get(['id', 'name', 'last_name', 'document_number', 'photo', 'signature']);

        $usuariosJs = $usuarios->map(fn ($u) => [
            'id' => (int) $u->id,
            'name' => trim(($u->name ?? '') . ' ' . ($u->last_name ?? '')),
            'document_number' => (string) ($u->document_number ?? ''),
            'photo' => $u->photo ? asset('storage/' . $u->photo) : null,
            'signature' => $u->signature ? asset('storage/' . $u->signature) : null,
        ])->values();

        $usaActaIntegrada = !$tienePlantillaExcel;
        $equipoDetalle = EquipoDetalle::ficha($equipo);

        return view('admin.equipos.bajas.form', compact(
            'equipo',
            'plantillaExcelPath',
            'renderedHtml',
            'inspeccion',
            'usuarios',
            'usuariosJs',
            'usaActaIntegrada',
            'equipoDetalle'
        ));
    }

    public function edit(Request $request, EquipoBaja $baja)
    {
        $empresaId = EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;
        $baja->loadMissing(['equipo', 'inspeccion']);
        $equipo = $baja->equipo;

        abort_unless($equipo, 404);
        $this->authorize('darDeBaja', $equipo);
        abort_if($empresaId && $equipo->empresa_id !== null && (int) $equipo->empresa_id !== (int) $empresaId, 404);
        EquipoDetalle::loadCompleto($equipo);

        $plantillaExcelPath = $baja->plantilla_excel_path ?: AppSetting::getValue($this->plantillaExcelKey());
        $tienePlantillaExcel = $plantillaExcelPath
            && Storage::disk('public')->exists($plantillaExcelPath)
            && $this->soportaLecturaExcel();

        $defaults = array_merge(
            EquipoDetalle::autoFields($equipo, (string) $baja->codigo_db),
            [
                'FECHA_BAJA' => $baja->fecha_baja ? $baja->fecha_baja->format('Y-m-d') : now()->format('Y-m-d'),
                'MOTIVO_BAJA' => (string) ($baja->motivo_baja ?? ''),
                'OBSERVACIONES_BAJA' => (string) ($baja->observaciones_baja ?? ''),
                'RESUMEN_BAJA' => (string) ($baja->motivo_baja ?? ''),
            ]
        );

        $savedHtml = (string) data_get($baja->form_data, 'edited_html', '');
        if ($savedHtml !== '') {
            $renderedHtml = $this->replaceTokens($savedHtml, $defaults);
        } elseif ($tienePlantillaExcel) {
            $templateHtml = $this->getPlantillaHtml(Storage::disk('public')->path($plantillaExcelPath));
            $renderedHtml = $this->replaceTokens($templateHtml, $defaults);
            $renderedHtml = $this->blankRemainingTokens($renderedHtml);
        } else {
            $renderedHtml = $this->buildActaPreviewHtml($equipo, (string) $baja->codigo_db, $defaults);
            $plantillaExcelPath = null;
        }
        $renderedHtml = $this->blankRemainingTokens($renderedHtml);

        $usuarios = User::query()
            ->where('active', true)
            ->when($empresaId, fn ($q) => $q->where(fn ($sub) => $sub->where('empresa_id', $empresaId)->orWhereNull('empresa_id')))
            ->orderBy('name')
            ->get(['id', 'name', 'last_name', 'document_number', 'photo', 'signature']);

        $usuariosJs = $usuarios->map(fn ($u) => [
            'id' => (int) $u->id,
            'name' => trim(($u->name ?? '') . ' ' . ($u->last_name ?? '')),
            'document_number' => (string) ($u->document_number ?? ''),
            'photo' => $u->photo ? asset('storage/' . $u->photo) : null,
            'signature' => $u->signature ? asset('storage/' . $u->signature) : null,
        ])->values();

        $inspeccion = $baja->inspeccion;
        $usaActaIntegrada = !$tienePlantillaExcel && $savedHtml === '';
        $equipoDetalle = EquipoDetalle::ficha($equipo);

        return view('admin.equipos.bajas.form', compact(
            'equipo',
            'plantillaExcelPath',
            'renderedHtml',
            'inspeccion',
            'usuarios',
            'usuariosJs',
            'baja',
            'usaActaIntegrada',
            'equipoDetalle'
        ));
    }

    public function store(StoreBajaRequest $request, Equipo $equipo)
    {
        $this->authorize('darDeBaja', $equipo);
        abort_unless($equipo->activo, 404);
        $plantillaExcelPath = AppSetting::getValue($this->plantillaExcelKey());
        $tienePlantillaExcel = $plantillaExcelPath
            && Storage::disk('public')->exists($plantillaExcelPath)
            && $this->soportaLecturaExcel();

        $inspeccionId = $request->input('inspeccion_id');

        return DB::transaction(function () use ($request, $equipo, $plantillaExcelPath, $tienePlantillaExcel, $inspeccionId) {
            $codigoIn = (string) $equipo->codigo;
            $codigoDb = $this->nextCodigoDb($equipo);

            $fechaBaja = now()->format('Y-m-d');
            $resumen = (string) $request->input('resumen_baja');

            $auto = EquipoDetalle::autoFields($equipo, $codigoDb);
            $merged = array_merge($auto, [
                'FECHA_BAJA' => $fechaBaja,
                'MOTIVO_BAJA' => $resumen,
                'RESUMEN_BAJA' => $resumen,
            ]);

            $finalHtml = $this->sanitizeEditedHtml((string) $request->input('edited_html', ''));
            if ($finalHtml !== '') {
                $finalHtml = $this->replaceTokens($finalHtml, $merged);
                $finalHtml = $this->blankRemainingTokens($finalHtml);
            } elseif ($tienePlantillaExcel) {
                $templateHtml = $this->getPlantillaHtml(Storage::disk('public')->path($plantillaExcelPath));
                $finalHtml = $this->blankRemainingTokens($this->replaceTokens($templateHtml, $merged));
            }

            // Preferir el HTML del Excel editable para que el PDF conserve exactamente el formato.
            if ($finalHtml !== '') {
                $pdfPath = $this->generatePdf($codigoDb, $finalHtml);
            } else {
                $pdfPath = $this->generateProfessionalPdf($equipo, $codigoDb, $codigoIn, $fechaBaja, $resumen);
            }

            $baja = EquipoBaja::create([
                'equipo_id' => $equipo->id,
                'equipo_inspeccion_id' => $inspeccionId,
                'codigo_db' => $codigoDb,
                'codigo_in' => $codigoIn,
                'creado_por' => auth()->id(),
                'fecha_baja' => $fechaBaja,
                'motivo_baja' => $resumen,
                'observaciones_baja' => null,
                'equipo_snapshot' => EquipoDetalle::snapshot($equipo),
                'form_data' => array_merge($merged, ['edited_html' => $finalHtml]),
                'plantilla_excel_path' => $tienePlantillaExcel ? $plantillaExcelPath : null,
                'pdf_path' => $pdfPath,
            ]);

            if ($inspeccionId) {
                EquipoInspeccion::query()
                    ->where('id', $inspeccionId)
                    ->where('equipo_id', $equipo->id)
                    ->update(['dado_de_baja' => true, 'equipo_baja_id' => $baja->id]);
            }

            $equipo->update([
                'codigo' => $codigoDb,
                'serial' => $codigoDb,
                'activo' => false,
            ]);

            $this->liberarCodigoIn($codigoIn);

            return redirect()->route('equipos.bajas.index')->with('success', "âœ… Equipo dado de baja ({$baja->codigo_db}). PDF generado desde el formato Excel y cÃ³digo de inventario liberado.");
        });
    }

    public function update(UpdateBajaRequest $request, EquipoBaja $baja)
    {
        $baja->loadMissing(['equipo', 'inspeccion']);
        $equipo = $baja->equipo;
        abort_unless($equipo, 404);
        $this->authorize('darDeBaja', $equipo);

        $empresaId = EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;
        abort_if($empresaId && $equipo->empresa_id !== null && (int) $equipo->empresa_id !== (int) $empresaId, 404);

        return DB::transaction(function () use ($request, $baja, $equipo) {
            $fechaBaja = $baja->fecha_baja ? $baja->fecha_baja->format('Y-m-d') : now()->format('Y-m-d');
            $resumen = (string) $request->input('resumen_baja');

            $auto = EquipoDetalle::autoFields($equipo, (string) $baja->codigo_db);
            $merged = array_merge($auto, [
                'FECHA_BAJA' => $fechaBaja,
                'MOTIVO_BAJA' => $resumen,
                'OBSERVACIONES_BAJA' => (string) ($baja->observaciones_baja ?? ''),
                'RESUMEN_BAJA' => $resumen,
            ]);

            $finalHtml = $this->sanitizeEditedHtml((string) $request->input('edited_html', ''));
            if ($finalHtml !== '') {
                $finalHtml = $this->replaceTokens($finalHtml, $merged);
                $finalHtml = $this->blankRemainingTokens($finalHtml);
            }

            if ($baja->pdf_path && Storage::disk('public')->exists($baja->pdf_path)) {
                Storage::disk('public')->delete($baja->pdf_path);
            }

            if ($finalHtml !== '') {
                $pdfPath = $this->generatePdf((string) $baja->codigo_db, $finalHtml);
            } else {
                $pdfPath = $this->generateProfessionalPdf(
                    $equipo,
                    (string) $baja->codigo_db,
                    (string) ($baja->codigo_in ?? $equipo->codigo),
                    $fechaBaja,
                    $resumen,
                    (string) ($baja->observaciones_baja ?? '')
                );
            }

            $newInspeccionId = $request->input('inspeccion_id') ? (int) $request->input('inspeccion_id') : null;
            $oldInspeccionId = $baja->equipo_inspeccion_id ? (int) $baja->equipo_inspeccion_id : null;

            if ($oldInspeccionId && $oldInspeccionId !== $newInspeccionId) {
                EquipoInspeccion::query()
                    ->where('id', $oldInspeccionId)
                    ->where('equipo_id', $equipo->id)
                    ->update(['dado_de_baja' => false, 'equipo_baja_id' => null]);
            }
            if ($newInspeccionId) {
                EquipoInspeccion::query()
                    ->where('id', $newInspeccionId)
                    ->where('equipo_id', $equipo->id)
                    ->update(['dado_de_baja' => true, 'equipo_baja_id' => $baja->id]);
            }

            $baja->update([
                'equipo_inspeccion_id' => $newInspeccionId,
                'motivo_baja' => $resumen,
                'form_data' => array_merge($merged, ['edited_html' => $finalHtml]),
                'pdf_path' => $pdfPath,
            ]);

            return redirect()->route('equipos.bajas.index')->with('success', "âœ… Baja {$baja->codigo_db} actualizada y PDF regenerado desde el formato.");
        });
    }

    public function previewPdf(Request $request, Equipo $equipo)
    {
        $this->authorize('darDeBaja', $equipo);
        abort_unless($equipo->activo, 404);

        $request->validate([
            'edited_html' => ['required', 'string'],
            'resumen_baja' => ['nullable', 'string', 'max:2000'],
        ]);

        $resumen = (string) $request->input('resumen_baja', '');
        $auto = EquipoDetalle::autoFields($equipo, null);
        $merged = array_merge($auto, [
            'FECHA_BAJA' => now()->format('Y-m-d'),
            'MOTIVO_BAJA' => $resumen,
            'RESUMEN_BAJA' => $resumen,
        ]);

        $html = (string) $request->input('edited_html');
        $html = $this->replaceTokens($html, $merged);
        $html = $this->blankRemainingTokens($html);

        $pdf = Pdf::loadHTML($this->wrapHtmlForPdf($html));
        $pdf->setPaper('a4', 'portrait');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function showPdf(EquipoBaja $baja)
    {
        $baja->loadMissing('equipo');
        if ($baja->equipo) {
            $this->authorize('view', $baja->equipo);
        }


        $needsExcel = $this->bajaShouldUseExcelPdf($baja);
        $pdfPath = $this->ensureBajaPdf($baja, $needsExcel);
        if ($baja->pdf_path !== $pdfPath) {
            $baja->update(['pdf_path' => $pdfPath]);
        }

        return response()->file(Storage::disk('public')->path($pdfPath), [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function downloadPdf(EquipoBaja $baja)
    {
        $baja->loadMissing('equipo');
        if ($baja->equipo) {
            $this->authorize('view', $baja->equipo);
        }


        $needsExcel = $this->bajaShouldUseExcelPdf($baja);
        $pdfPath = $this->ensureBajaPdf($baja, $needsExcel);
        if ($baja->pdf_path !== $pdfPath) {
            $baja->update(['pdf_path' => $pdfPath]);
        }

        return Storage::disk('public')->download($pdfPath, 'Acta-Baja-' . $baja->codigo_db . '.pdf');
    }

    /** True si hay plantilla/HTML Excel y el PDF actual aÃºn es el acta genÃ©rica o no existe. */
    private function bajaShouldUseExcelPdf(EquipoBaja $baja): bool
    {
        $savedHtml = (string) data_get($baja->form_data, 'edited_html', '');
        $plantillaExcelPath = $baja->plantilla_excel_path ?: AppSetting::getValue($this->plantillaExcelKey());
        $tieneExcel = ($savedHtml !== '')
            || (
                $plantillaExcelPath
                && Storage::disk('public')->exists($plantillaExcelPath)
                && $this->soportaLecturaExcel()
            );

        if (!$tieneExcel) {
            return false;
        }

        if (!$baja->pdf_path || !Storage::disk('public')->exists($baja->pdf_path)) {
            return true;
        }

        // Los PDF antiguos (acta genÃ©rica o Excel sin marco) se regeneran.
        $path = (string) $baja->pdf_path;

        return str_contains($path, 'Acta-Baja-') || !str_contains($path, 'Excel-');
    }

    /**
     * Genera o regenera el PDF.
     * Preferencia: HTML editable del Excel â†’ plantilla Excel â†’ acta profesional integrada.
     */
    private function ensureBajaPdf(EquipoBaja $baja, bool $preferExcel = false): string
    {
        $baja->loadMissing(['equipo']);
        $equipo = $baja->equipo;
        if (!$equipo) {
            abort(404);
        }

        $savedHtml = (string) data_get($baja->form_data, 'edited_html', '');
        $plantillaExcelPath = $baja->plantilla_excel_path ?: AppSetting::getValue($this->plantillaExcelKey());
        $tieneExcel = $plantillaExcelPath
            && Storage::disk('public')->exists($plantillaExcelPath)
            && $this->soportaLecturaExcel();

        // Si ya hay PDF y no se fuerza regeneraciÃ³n Excel, reutilizar.
        if (
            !$preferExcel
            && $baja->pdf_path
            && Storage::disk('public')->exists($baja->pdf_path)
            && $savedHtml === ''
            && !$tieneExcel
        ) {
            return $baja->pdf_path;
        }

        // Si hay HTML/Excel disponible, regenerar para conservar el formato exacto.
        if ($preferExcel || $savedHtml !== '' || $tieneExcel) {
            EquipoDetalle::loadCompleto($equipo);

            $fechaBaja = $baja->fecha_baja ? $baja->fecha_baja->format('Y-m-d') : now()->format('Y-m-d');
            $motivo = (string) ($baja->motivo_baja ?? '');
            $observaciones = (string) ($baja->observaciones_baja ?? '');
            $codigoDb = (string) $baja->codigo_db;
            $codigoIn = (string) ($baja->codigo_in ?? '');

            $merged = array_merge(EquipoDetalle::autoFields($equipo, $codigoDb), [
                'FECHA_BAJA' => $fechaBaja,
                'MOTIVO_BAJA' => $motivo,
                'OBSERVACIONES_BAJA' => $observaciones,
                'RESUMEN_BAJA' => $motivo,
            ]);

            if ($savedHtml !== '') {
                $html = $this->blankRemainingTokens($this->replaceTokens($savedHtml, $merged));
                $newPath = $this->generatePdf($codigoDb, $html);
            } elseif ($tieneExcel) {
                $templateHtml = $this->getPlantillaHtml(Storage::disk('public')->path($plantillaExcelPath));
                $html = $this->blankRemainingTokens($this->replaceTokens($templateHtml, $merged));
                $newPath = $this->generatePdf($codigoDb, $html);
            } else {
                $newPath = $this->generateProfessionalPdf($equipo, $codigoDb, $codigoIn, $fechaBaja, $motivo, $observaciones);
            }

            if ($baja->pdf_path && $baja->pdf_path !== $newPath && Storage::disk('public')->exists($baja->pdf_path)) {
                Storage::disk('public')->delete($baja->pdf_path);
            }

            return $newPath;
        }

        if ($baja->pdf_path && Storage::disk('public')->exists($baja->pdf_path)) {
            return $baja->pdf_path;
        }

        return $this->generateProfessionalPdf(
            $equipo,
            (string) $baja->codigo_db,
            (string) ($baja->codigo_in ?? ''),
            $baja->fecha_baja ? $baja->fecha_baja->format('Y-m-d') : now()->format('Y-m-d'),
            (string) ($baja->motivo_baja ?? ''),
            (string) ($baja->observaciones_baja ?? '')
        );
    }

    public function eliminar(Request $request)
    {
        $this->authorize('viewBajas', Equipo::class);

        $request->validate([
            'equipo_id' => ['required', 'integer', 'exists:equipos,id'],
            'password' => ['required', 'string'],
        ]);

        $adminUser = \App\Models\User::query()
            ->whereHas('role', fn ($q) => $q->whereRaw('LOWER(name) = ?', ['administrador']))
            ->get()
            ->first(fn ($u) => Hash::check($request->password, $u->password));

        if (!$adminUser) {
            return redirect()->route('equipos.bajas.index')->with('error', 'Contraseña de administrador incorrecta.');
        }

        $equipo = Equipo::query()->findOrFail($request->integer('equipo_id'));
        $this->authorize('delete', $equipo);
        $codigo = (string) ($equipo->codigo ?? $equipo->id);
        // Si aÃºn tiene cÃ³digo de inventario (caso raro), liberarlo; en baja normal ya se liberÃ³ el IN.
        CodigoEquipoService::liberarInventario((string) ($equipo->codigo ?? ''));
        $equipo->delete();

        return redirect()->route('equipos.bajas.index')->with('success', "âœ… Equipo eliminado ({$codigo}). La historia (hoja de vida, inspecciones, etc.) se conserva.");
    }

 
    private function cachePlantillaHtmlSimple(string $absolutePath): ?string
    {
        try {
            $html = $this->excelToHtmlFull($absolutePath);
            $maxLen = 2 * 1024 * 1024;
            if ($html !== null && strlen($html) > 0 && strlen($html) < $maxLen) {
                $result = $this->extractBodyAndStyles($html);
                unset($html);
                $filename = 'bajas/plantillas/plantilla-' . now()->format('YmdHis') . '.html';
                Storage::disk('public')->put($filename, $result);
            return $filename;
            }
            $html = $this->excelToHtmlSimple($absolutePath);
            $filename = 'bajas/plantillas/plantilla-' . now()->format('YmdHis') . '.html';
            Storage::disk('public')->put($filename, $html);
            return $filename;
        } catch (\Throwable $e) {
            try {
                $html = $this->excelToHtmlSimple($absolutePath);
                $filename = 'bajas/plantillas/plantilla-' . now()->format('YmdHis') . '.html';
                Storage::disk('public')->put($filename, $html);
                return $filename;
            } catch (\Throwable $e2) {
                return null;
            }
        }
    }

    
    

    private function getPlantillaHtml(string $excelAbsolutePath): string
    {
        $maxCacheSize = 2 * 1024 * 1024;
        $htmlPath = AppSetting::getValue($this->plantillaHtmlKey());
        if ($htmlPath && Storage::disk('public')->exists($htmlPath)) {
            $fullPath = Storage::disk('public')->path($htmlPath);
            if (filesize($fullPath) < $maxCacheSize) {
                $html = Storage::disk('public')->get($htmlPath);
                if ($html !== false && $html !== '') {
                    return (string) $html;
                }
            }
        }

        // Si no hay cachÃ© vÃ¡lida, intentar generar vista completa (con estilos del Excel)
        // para que la ediciÃ³n se vea "tipo PDF", similar a inspecciÃ³n/hoja de vida.
        $fullHtml = $this->excelToHtmlFull($excelAbsolutePath);
        if ($fullHtml !== null && strlen($fullHtml) > 0 && strlen($fullHtml) < $maxCacheSize) {
            $result = $this->extractBodyAndStyles($fullHtml);
            $filename = 'bajas/plantillas/plantilla-' . now()->format('YmdHis') . '.html';
            Storage::disk('public')->put($filename, $result);
            AppSetting::setValue($this->plantillaHtmlKey(), $filename);
            return $result;
        }

        return $this->excelToHtmlSimple($excelAbsolutePath);
    }

    /** Extrae body + estilos del HTML para mostrarlo correctamente. */
    private function extractBodyAndStyles(string $fullHtml): string
    {
        $result = '';
        $headEnd = stripos($fullHtml, '</head>');
        if ($headEnd !== false) {
            $headStart = stripos($fullHtml, '<head');
            if ($headStart !== false) {
                $contentStart = strpos($fullHtml, '>', $headStart) + 1;
                $headContent = substr($fullHtml, $contentStart, $headEnd - $contentStart);
                if (preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $headContent, $m)) {
                    $result .= '<style>' . implode("\n", array_map('trim', $m[1])) . '</style>';
                }
            }
        }
        $bodyOpen = stripos($fullHtml, '<body');
        if ($bodyOpen !== false) {
            $bodyContentStart = strpos($fullHtml, '>', $bodyOpen) + 1;
            $bodyClose = stripos($fullHtml, '</body>', $bodyContentStart);
            if ($bodyClose !== false) {
                $result .= trim(substr($fullHtml, $bodyContentStart, $bodyClose - $bodyContentStart));
                return $result;
            }
        }
        return $result ?: $fullHtml;
    }

    /** Formato completo como inspecciÃ³n/hoja de vida (Html writer). Retorna null si falla por memoria. */
    private function excelToHtmlFull(string $absolutePath): ?string
    {
        $prevLimit = ini_get('memory_limit');
        ini_set('memory_limit', '2560M');
        try {
            $spreadsheet = IOFactory::load($absolutePath);
            $writer = new SpreadsheetHtmlWriter($spreadsheet);
            $writer->setSheetIndex(0);
            $writer->setPreCalculateFormulas(false);
            if (method_exists($writer, 'setEmbedImages')) {
                $writer->setEmbedImages(true);
            }
            ob_start();
            $writer->save('php://output');
            $html = (string) ob_get_clean();
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $writer);
            return $html;
        } catch (\Throwable $e) {
            return null;
        } finally {
            $restoreTo = $prevLimit ?: '512M';
            @ini_set('memory_limit', $restoreTo);
        }
    }

    /**
     * Fallback: convierte Excel a HTML simple (sin formato completo) cuando no hay cache.
     */
    private function excelToHtmlSimple(string $absolutePath): string
    {
        $reader = IOFactory::createReaderForFile($absolutePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($absolutePath);
        $sheet = $spreadsheet->getActiveSheet();

        $highestRow = min($sheet->getHighestRow(), 200);
        $highestCol = $sheet->getHighestColumn();
        $highestColIndex = min(Coordinate::columnIndexFromString($highestCol), 50);

        $mergeRanges = [];
        foreach ($sheet->getMergeCells() as $range) {
            $mergeRanges[] = $range;
        }

        $isMasterOf = function (int $row, int $col) use ($mergeRanges): ?array {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $coord = $colLetter . $row;
            foreach ($mergeRanges as $range) {
                if (Coordinate::coordinateIsInsideRange($coord, $range)) {
                    [$from, $to] = explode(':', $range);
                    [$fromCol, $fromRow] = Coordinate::indexesFromString($from);
                    [$toCol, $toRow] = Coordinate::indexesFromString($to);
                    if ($fromRow === $row && $fromCol === $col) {
                        return ['rowspan' => $toRow - $fromRow + 1, 'colspan' => $toCol - $fromCol + 1];
                    }
                    return ['rowspan' => 0, 'colspan' => 0];
                }
            }
            return null;
        };

        $covered = [];
        foreach ($mergeRanges as $range) {
            [$from, $to] = explode(':', $range);
            [$fromCol, $fromRow] = Coordinate::indexesFromString($from);
            [$toCol, $toRow] = Coordinate::indexesFromString($to);
            for ($r = $fromRow; $r <= $toRow; $r++) {
                for ($c = $fromCol; $c <= $toCol; $c++) {
                    if ($r !== $fromRow || $c !== $fromCol) {
                        $covered[$r][$c] = true;
                    }
                }
            }
        }

        $html = '<table class="baja-table" border="1" cellpadding="4" cellspacing="0" style="width:100%;border-collapse:collapse;">';
        for ($r = 1; $r <= $highestRow; $r++) {
            $html .= '<tr>';
            for ($c = 1; $c <= $highestColIndex; $c++) {
                if (!empty($covered[$r][$c])) {
                    continue;
                }
                $merge = $isMasterOf($r, $c);
                $attrs = '';
                if ($merge !== null) {
                    if ($merge['rowspan'] > 0) {
                        $attrs = ' rowspan="' . $merge['rowspan'] . '" colspan="' . $merge['colspan'] . '"';
                    } else {
                        continue;
                    }
                }
                $cell = $sheet->getCellByColumnAndRow($c, $r);
                $value = $cell->getFormattedValue();
                $style = $cell->getStyle();
                $bold = $style->getFont()->getBold();
                $tag = $bold ? 'strong' : 'span';
                if ($value !== null && $value !== '') {
                    $value = nl2br(e((string) $value));
                } else {
                    $value = '&nbsp;';
                }
                $html .= '<td' . $attrs . '><' . $tag . '>' . $value . '</' . $tag . '></td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table>';

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $reader);

        return $html;
    }

    private function blankRemainingTokens(string $html): string
    {
        return (string) preg_replace('/\{\{\s*[A-Z0-9_\-]+\s*\}\}/', '', $html);
    }

    private function replaceTokens(string $html, array $data): string
    {
        foreach ($data as $key => $value) {
            $token = '{{' . strtoupper((string) $key) . '}}';
            $html = str_replace($token, e((string) $value), $html);
        }

        return $html;
    }

    private function sanitizeEditedHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        return HtmlSanitizer::sanitizeTemplateHtml($html);
    }

    private function nextCodigoDb(Equipo $equipo): string
    {
        $prefijo = EmpresaContext::prefijo();
        $tag = $this->empresaCodeTag($equipo->empresa_id, 'baja_tag', 'DB');
        $aliasRaw = (string) ($equipo->tipoEquipo?->alias ?: $equipo->tipoEquipo?->nombre ?: 'GEN');
        $alias = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $aliasRaw) ?: 'GEN', 0, 4));
        $patron = $prefijo . '-' . $alias . '-' . $tag . '-%';

        $maxEquipo = DB::table('equipos')
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(codigo, '-', -1) AS UNSIGNED)) as max_num")
            ->whereNotNull('codigo')
            ->where('codigo', 'like', $patron)
            ->value('max_num');

        $maxBaja = DB::table('equipos_baja')
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(codigo_db, '-', -1) AS UNSIGNED)) as max_num")
            ->whereNotNull('codigo_db')
            ->where('codigo_db', 'like', $patron)
            ->value('max_num');

        $max = max((int) $maxEquipo, (int) $maxBaja);
        $next = $max + 1;

        return $prefijo . '-' . $alias . '-' . $tag . '-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function empresaCodeTag(?int $empresaId, string $key, string $default): string
    {
        if (!$empresaId) {
            return $default;
        }
        $empresa = Empresa::find($empresaId);
        $tag = strtoupper((string) ($empresa?->code_settings[$key] ?? $default));
        $tag = preg_replace('/[^A-Z0-9]/', '', $tag) ?: $default;
        return $tag;
    }

    private function liberarCodigoIn(string $codigoIn): void
    {
        CodigoEquipoService::liberarInventario($codigoIn);
    }

    private function categoriaEquipo(string $codigo): string
    {
        if (CodigoEquipoService::esMaterial($codigo)) {
            return 'material';
        }
        if (CodigoEquipoService::esAuditoria($codigo)) {
            return 'auditoria';
        }
        if (CodigoEquipoService::esBaja($codigo)) {
            return 'baja';
        }

        return 'inventario';
    }


    private function generatePdf(string $codigoDb, string $html): string
    {
        $pdf = Pdf::loadHTML($this->wrapHtmlForPdf($html));
        $pdf->setPaper('a4', 'portrait');

        $safeCode = preg_replace('/[^A-Za-z0-9\-_]/', '_', $codigoDb) ?: 'baja';
        $filename = 'bajas/pdfs/Excel-' . $safeCode . '-' . now()->format('YmdHis') . '.pdf';
        Storage::disk('public')->put($filename, $pdf->output());

        return $filename;
    }

    private function generateProfessionalPdf(
        Equipo $equipo,
        string $codigoDb,
        string $codigoIn,
        string $fechaBaja,
        string $motivo,
        string $observaciones = ''
    ): string {
        $equipo->loadMissing(['empresa', 'sede', 'bodega', 'tipoEquipo']);
        $user = auth()->user();
        $empresaNombre = (string) ($equipo->empresa?->nombre ?? EmpresaContext::empresaActiva()?->nombre ?? 'SAMS');

        $html = view('pdfs.acta_baja', [
            'empresaNombre' => $empresaNombre,
            'codigoDb' => $codigoDb,
            'codigoIn' => $codigoIn,
            'nombre' => (string) $equipo->nombre,
            'serial' => (string) ($equipo->serial ?? ''),
            'empresa' => (string) ($equipo->empresa?->nombre ?? ''),
            'sede' => (string) ($equipo->sede?->nombre ?? ''),
            'bodega' => (string) ($equipo->bodega?->nombre ?? ''),
            'fechaCompra' => $equipo->fecha_compra ? $equipo->fecha_compra->format('Y-m-d') : '',
            'factura' => (string) ($equipo->numero_factura ?? ''),
            'fechaBaja' => $fechaBaja,
            'motivo' => $motivo,
            'observaciones' => $observaciones,
            'usuarioNombre' => trim(($user?->name ?? '') . ' ' . ($user?->last_name ?? '')),
            'registradoEn' => now()->format('Y-m-d H:i'),
        ])->render();

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('a4', 'portrait');

        $safeCode = preg_replace('/[^A-Za-z0-9\-_]/', '_', $codigoDb) ?: 'baja';
        $filename = 'bajas/pdfs/Acta-Baja-' . $safeCode . '-' . now()->format('YmdHis') . '.pdf';
        Storage::disk('public')->put($filename, $pdf->output());

        return $filename;
    }

    /** Vista previa HTML del acta integrada (cuando no hay plantilla Excel). */
    private function buildActaPreviewHtml(Equipo $equipo, ?string $codigoDb, array $defaults): string
    {
        $equipo->loadMissing(['empresa', 'sede', 'bodega']);

        return '<div style="font-family:DejaVu Sans,sans-serif;color:#1f2937;">'
            . '<div style="border-bottom:3px solid #0f766e;padding-bottom:10px;margin-bottom:14px;">'
            . '<div style="font-size:16px;font-weight:bold;color:#0f766e;">Acta de Baja â€” SAMS</div>'
            . '<div style="font-size:11px;color:#64748b;">Documento oficial de equipo dado de baja</div>'
            . '</div>'
            . '<p><strong>CÃ³digo DB:</strong> ' . e((string) ($codigoDb ?: '(se asignarÃ¡ al guardar)')) . '</p>'
            . '<p><strong>CÃ³digo IN:</strong> ' . e((string) ($defaults['CODIGO_IN'] ?? $equipo->codigo)) . '</p>'
            . '<p><strong>Equipo:</strong> ' . e((string) $equipo->nombre) . '</p>'
            . '<p><strong>Empresa:</strong> ' . e((string) ($equipo->empresa?->nombre ?? '')) . '</p>'
            . '<p><strong>Fecha:</strong> ' . e((string) ($defaults['FECHA_BAJA'] ?? now()->format('Y-m-d'))) . '</p>'
            . '<p style="margin-top:12px;color:#64748b;font-size:12px;">Complete el resumen de la baja arriba. Al guardar se generarÃ¡ el PDF profesional del acta y el cÃ³digo de inventario quedarÃ¡ disponible.</p>'
            . '</div>';
    }

    private function wrapHtmlForPdf(string $body): string
    {
        // Marco profesional alrededor del documento (compatible con DomPDF).
        $css = '@page { margin: 8mm; }'
            . 'html,body{margin:0;padding:0;font-family:DejaVu Sans,sans-serif;font-size:11px;color:#222;}'
            . 'img{max-width:100%;height:auto;}'
            . 'table{border-collapse:collapse;}'
            . '.baja-signature-inline{max-width:180px;max-height:70px;}'
            . '.pdf-page-frame{'
            . 'border:2.5px solid #1e3a5f;'
            . 'padding:3px;'
            . 'min-height:262mm;'
            . '}'
            . '.pdf-page-frame-inner{'
            . 'border:1px solid #1e3a5f;'
            . 'padding:8px 10px;'
            . 'min-height:258mm;'
            . '}';

        $framed = '<div class="pdf-page-frame"><div class="pdf-page-frame-inner">' . $body . '</div></div>';

        return '<!DOCTYPE html><html><head><meta charset="utf-8"><style>' . $css . '</style></head><body>' . $framed . '</body></html>';
    }

    private function mapEquipoForJs(Equipo $e): array
    {
        $imgEtiqueta = $e->imagenes->firstWhere('tipo', 'etiqueta');
        $imgGeneral = $e->imagenes->firstWhere('tipo', 'general');
        $imgPath = $imgEtiqueta?->path ?: $imgGeneral?->path;

        return [
            'id' => $e->id,
            'codigo' => $e->codigo,
            'nombre' => $e->nombre,
            'serial' => $e->serial,
            'categoria' => $this->categoriaEquipo((string) $e->codigo),
            'img' => $imgPath ? asset('storage/' . $imgPath) : null,
            'img_general' => $imgGeneral?->path ? asset('storage/' . $imgGeneral->path) : null,
            'img_etiqueta' => $imgEtiqueta?->path ? asset('storage/' . $imgEtiqueta->path) : null,
        ];
    }

}
