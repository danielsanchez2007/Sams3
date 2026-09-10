<?php

namespace App\Http\Controllers;

use App\Models\ClaseEquipo;
use App\Models\Equipo;
use App\Models\EquipoInspeccion;
use App\Models\User;
use App\Models\InspeccionPlantilla;
use App\Services\HojaVidaAutoFields;
use App\Support\HtmlSanitizer;
use App\Support\UploadedFileStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Html as SpreadsheetHtmlWriter;

class InspeccionController extends Controller
{
    private function ensureCanEditInspeccion(): void
    {
        $this->assertCanEditModule('inspeccion');
    }

    private function ensureCanViewInspeccion(): void
    {
        $this->assertCanViewModule('inspeccion');
    }

    public function index(Request $request)
    {
        $this->ensureCanViewInspeccion();
        $empresaId = $this->resolveTenantEmpresaId();
        $clases = ClaseEquipo::query()
            ->with('tipoEquipo')
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'tipo_equipo_id']);

        $plantillasByClase = InspeccionPlantilla::query()
            ->whereNotNull('clase_equipo_id')
            ->pluck('id', 'clase_equipo_id');

        $clasesSinFormato = $clases->filter(fn ($c) => !isset($plantillasByClase[$c->id]));

        return view('admin.inspeccion.index', compact('clases', 'plantillasByClase', 'clasesSinFormato'));
    }

    public function storePlantilla(Request $request)
    {
        $this->ensureCanEditInspeccion();

        $request->validate([
            'clase_equipo_id' => ['required', 'integer', 'exists:clase_equipos,id'],
            'plantilla_excel' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $empresaId = \App\Services\EmpresaContext::empresaId() ?? auth()->user()?->empresa_id;
        $clase = ClaseEquipo::query()->whereKey($request->integer('clase_equipo_id'))->firstOrFail();

        if ($empresaId && (int) $clase->empresa_id !== (int) $empresaId) {
            abort(403, 'No puedes asignar formato a una clase de otra empresa.');
        }

        if (!$clase->tipo_equipo_id) {
            return redirect()->route('inspeccion.index')
                ->with('error', 'La clase de equipo seleccionada no tiene tipo asociado.');
        }

        $file = $request->file('plantilla_excel');
        try {
            $path = UploadedFileStorage::storePublicSpreadsheet($file, 'inspeccion/plantillas');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('inspeccion.index')
                ->with('error', 'No se pudo guardar la plantilla.');
        }

        $plantilla = InspeccionPlantilla::query()->firstOrNew([
            'clase_equipo_id' => $clase->id,
        ]);

        if ($plantilla->plantilla_excel_path
            && $plantilla->plantilla_excel_path !== $path
            && Storage::disk('public')->exists($plantilla->plantilla_excel_path)) {
            Storage::disk('public')->delete($plantilla->plantilla_excel_path);
        }

        $plantilla->fill([
            'tipo_equipo_id' => $clase->tipo_equipo_id,
            'plantilla_excel_path' => $path,
        ]);
        $plantilla->save();

        return redirect()->route('inspeccion.index')->with('success', 'Formato de inspección asignado a la clase de equipo.');
    }

    public function equipos(Request $request, ClaseEquipo $clase)
    {
        $this->ensureCanViewInspeccion();
        $empresaId = $this->resolveTenantEmpresaId();
        if ($empresaId && (int) $clase->empresa_id !== (int) $empresaId) {
            abort(403);
        }
        $plantilla = InspeccionPlantilla::query()->where('clase_equipo_id', $clase->id)->first();
        if (!$plantilla || !Storage::disk('public')->exists($plantilla->plantilla_excel_path)) {
            return redirect()->route('inspeccion.index')->with('error', 'Esta clase de equipo no tiene formato de inspección asignado.');
        }

        $hoy = now()->startOfDay();

        $equipos = Equipo::query()
            ->with(['empresa', 'sede', 'bodega', 'imagenes'])
            ->where('clase_equipo_id', $clase->id)
            ->orderByRaw("CASE WHEN codigo LIKE 'IN-%' THEN CAST(SUBSTRING(codigo,4) AS UNSIGNED) END ASC")
            ->orderBy('codigo')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        $equipoIds = $equipos->getCollection()->pluck('id');
        $ultimaInspeccionByEquipo = EquipoInspeccion::query()
            ->whereIn('equipo_id', $equipoIds)
            ->orderByDesc('validez_hasta')
            ->get()
            ->unique('equipo_id')
            ->keyBy('equipo_id');

        $inspeccionesByEquipo = EquipoInspeccion::query()
            ->whereIn('equipo_id', $equipoIds)
            ->orderByDesc('fecha_inspeccion')
            ->get()
            ->groupBy('equipo_id');

        $canEditInspeccion = $this->moduleAuthz()->canEditModule('inspeccion');

        return view('admin.inspeccion.equipos', compact('clase', 'plantilla', 'equipos', 'ultimaInspeccionByEquipo', 'inspeccionesByEquipo', 'hoy', 'canEditInspeccion'));
    }

    public function form(Request $request, Equipo $equipo)
    {
        $this->ensureCanEditInspeccion();
        abort_unless($equipo->activo, 404);

        $equipo->loadMissing(['imagenes', 'empresa', 'sede', 'bodega', 'tipoEquipo', 'claseEquipo']);

        $plantilla = InspeccionPlantilla::query()->where('clase_equipo_id', $equipo->clase_equipo_id)->first();
        if (!$plantilla || !Storage::disk('public')->exists($plantilla->plantilla_excel_path)) {
            return redirect()->route('inspeccion.equipos', $equipo->claseEquipo)->with('error', 'No hay formato de inspección para esta clase de equipo.');
        }

        $ultima = $equipo->inspecciones()->orderByDesc('validez_hasta')->first();
        $hoy = now()->startOfDay();
        $puedeInspeccionar = !$ultima || !$ultima->validez_hasta || $ultima->validez_hasta->lt($hoy);
        $requiereObligatoria = $ultima && $ultima->validez_hasta && $ultima->validez_hasta->gte($hoy);

        $fechaHoy = now()->format('Y-m-d');
        $templateHtml = $this->excelToHtml(Storage::disk('public')->path($plantilla->plantilla_excel_path));
        $defaults = $this->buildAutoFields($equipo);
        $defaults['FECHA_HOY'] = $fechaHoy;
        $defaults['FECHA_INSPECCION'] = $fechaHoy;
        $renderedHtml = $this->replaceTokens($templateHtml, $defaults);
        $renderedHtml = $this->blankRemainingTokens($renderedHtml);
        $renderedHtml = $this->cleanTemplateArtifacts($renderedHtml);

        $clase = $equipo->claseEquipo;
        $panel = $this->inspectionPanelData($equipo);
        $usuarios = $panel['usuarios'];
        $usuariosJs = $panel['usuariosJs'];
        $hvChips = $panel['hvChips'];
        $panelUserId = $panel['panelUserId'];
        $inspectorUserId = $panel['panelUserId'];

        return view('admin.inspeccion.form', compact('equipo', 'plantilla', 'clase', 'renderedHtml', 'fechaHoy', 'puedeInspeccionar', 'requiereObligatoria', 'usuarios', 'usuariosJs', 'hvChips', 'panelUserId', 'inspectorUserId'));
    }

    public function verificarObligatoria(Request $request)
    {
        $request->validate(['password' => ['required', 'string']]);
        if (!Hash::check($request->input('password'), auth()->user()->getAuthPassword())) {
            return response()->json(['ok' => false, 'message' => 'Contraseña incorrecta.'], 422);
        }
        return response()->json(['ok' => true]);
    }

    public function store(Request $request, Equipo $equipo)
    {
        $this->ensureCanEditInspeccion();
        abort_unless($equipo->activo, 404);

        $plantilla = InspeccionPlantilla::query()->where('clase_equipo_id', $equipo->clase_equipo_id)->first();
        if (!$plantilla || !Storage::disk('public')->exists($plantilla->plantilla_excel_path)) {
            return redirect()->route('inspeccion.equipos', $equipo->claseEquipo)->with('error', 'No hay formato de inspección para esta clase de equipo.');
        }

        $request->validate([
            'edited_html' => ['required', 'string'],
            'validez_hasta' => ['required', 'date', 'after_or_equal:today'],
            'cantidad_usuarios' => ['required', 'integer', 'min:1', 'max:10'],
            'inspector_user_id' => ['required', 'exists:users,id'],
            'selected_user_ids' => ['nullable', 'array'],
            'selected_user_ids.*' => ['nullable', 'exists:users,id'],
            'inspeccion_obligatoria' => ['nullable', 'in:1'],
            'password_obligatoria' => ['required_if:inspeccion_obligatoria,1', 'nullable', 'string'],
        ]);

        $hoy = now()->startOfDay();
        $ultima = $equipo->inspecciones()->orderByDesc('validez_hasta')->first();
        $puedeInspeccionar = !$ultima || !$ultima->validez_hasta || $ultima->validez_hasta->lt($hoy);
        $inspeccionObligatoria = (bool) $request->input('inspeccion_obligatoria');

        if (!$puedeInspeccionar && !$inspeccionObligatoria) {
            return redirect()->route('inspeccion.form', $equipo)->with('error', 'No se puede realizar una nueva inspección hasta que pase la fecha de validez de la anterior.');
        }

        if ($inspeccionObligatoria && !$puedeInspeccionar) {
            if (!Hash::check($request->input('password_obligatoria', ''), auth()->user()->getAuthPassword())) {
                return redirect()->route('inspeccion.form', $equipo)->with('error', 'Contraseña incorrecta para inspección obligatoria.');
            }
        }

        $validezHasta = $request->date('validez_hasta');
        $fechaInspeccion = now()->toDateString();

        $selectedIds = array_values(array_filter(array_map('intval', (array) ($request->input('selected_user_ids') ?? []))));

        $sanitizedHtml = HtmlSanitizer::sanitizeUserHtml((string) $request->input('edited_html'));

        $inspeccion = EquipoInspeccion::query()->create([
            'equipo_id' => $equipo->id,
            'fecha_inspeccion' => $fechaInspeccion,
            'validez_hasta' => $validezHasta,
            'edited_html' => $sanitizedHtml,
            'pdf_path' => null,
            'inspeccion_obligatoria' => $inspeccionObligatoria,
            'user_id' => auth()->id(),
            'inspector_user_id' => (int) $request->input('inspector_user_id'),
            'selected_user_ids' => $selectedIds,
        ]);

        $wrapped = $this->buildPdfHtml($inspeccion);
        $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false])->loadHTML($wrapped)->setPaper('a4');
        $out = $pdf->output();
        $filename = 'inspeccion/pdf/' . $equipo->id . '-' . $inspeccion->id . '-' . now()->format('YmdHis') . '.pdf';
        Storage::disk('public')->makeDirectory('inspeccion/pdf');
        Storage::disk('public')->put($filename, $out);
        $inspeccion->update(['pdf_path' => $filename]);

        return redirect()->route('inspeccion.equipos', $equipo->claseEquipo)->with('success', 'Inspección guardada. Ya puedes descargar el PDF.');
    }

    public function edit(EquipoInspeccion $inspeccion)
    {
        $this->ensureCanEditInspeccion();
        $equipo = $inspeccion->equipo;
        abort_unless($equipo->activo, 404);
        $clase = $equipo->claseEquipo;

        $panel = $this->inspectionPanelData($equipo);
        $usuarios = $panel['usuarios'];
        $usuariosJs = $panel['usuariosJs'];
        $hvChips = $panel['hvChips'];
        $panelUserId = $panel['panelUserId'];

        return view('admin.inspeccion.edit', compact('inspeccion', 'equipo', 'clase', 'usuarios', 'usuariosJs', 'hvChips', 'panelUserId'));
    }

    public function update(Request $request, EquipoInspeccion $inspeccion)
    {
        $this->ensureCanEditInspeccion();
        $equipo = $inspeccion->equipo;
        abort_unless($equipo->activo, 404);

        $request->validate([
            'edited_html' => ['required', 'string'],
            'cantidad_usuarios' => ['required', 'integer', 'min:1', 'max:10'],
            'inspector_user_id' => ['required', 'exists:users,id'],
            'selected_user_ids' => ['nullable', 'array'],
            'selected_user_ids.*' => ['nullable', 'exists:users,id'],
        ]);

        $selectedIds = array_values(array_filter(array_map('intval', (array) ($request->input('selected_user_ids') ?? []))));

        $sanitizedHtml = HtmlSanitizer::sanitizeUserHtml((string) $request->input('edited_html'));

        $inspeccion->update([
            'edited_html' => $sanitizedHtml,
            'inspector_user_id' => (int) $request->input('inspector_user_id'),
            'selected_user_ids' => $selectedIds,
        ]);

        $wrapped = $this->buildPdfHtml($inspeccion);
        $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false])->loadHTML($wrapped)->setPaper('a4');
        $out = $pdf->output();
        $filename = 'inspeccion/pdf/' . $equipo->id . '-' . $inspeccion->id . '-' . now()->format('YmdHis') . '.pdf';
        Storage::disk('public')->makeDirectory('inspeccion/pdf');
        Storage::disk('public')->put($filename, $out);
        $inspeccion->update(['pdf_path' => $filename]);

        return redirect()->route('inspeccion.equipos', $equipo->claseEquipo)->with('success', 'Inspección actualizada.');
    }

    public function download(EquipoInspeccion $inspeccion)
    {
        $this->ensureCanViewInspeccion();
        if (!$inspeccion->pdf_path || !Storage::disk('public')->exists($inspeccion->pdf_path)) {
            return redirect()->route('inspeccion.edit', $inspeccion)->with('error', 'No hay PDF generado para esta inspección.');
        }
        $nombre = 'inspeccion-' . ($inspeccion->equipo->codigo ?: $inspeccion->equipo->id) . '.pdf';
        return Storage::disk('public')->download($inspeccion->pdf_path, $nombre);
    }

    /**
     * Vista previa HTML para el modal "Exportar PDF" (igual que en Hoja de Vida).
     */
    public function html(EquipoInspeccion $inspeccion)
    {
        $this->ensureCanViewInspeccion();
        $html = (string) $inspeccion->edited_html;
        $html = $this->convertStorageImagesForPdf($html);

        $extracted = $this->extractBodyAndStylesForPdf($html);
        $body = $this->sanitizeHtmlForPdf($extracted['body']);
        $body = $this->removeEmptyTableCells($body);

        $wrapped = $this->wrapHtmlForPdf($body, $extracted['styles'], $inspeccion, false);

        return response($wrapped, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
    }

    public function darDeBaja(Request $request, Equipo $equipo)
    {
        $this->ensureCanEditInspeccion();
        abort_unless($equipo->activo, 404);
        $url = route('equipos.bajas.form', $equipo);
        $inspeccionId = $request->query('inspeccion_id');
        if ($inspeccionId) {
            $url .= '?inspeccion_id=' . urlencode($inspeccionId);
        }
        return redirect($url);
    }

    private function excelToHtml(string $absolutePath): string
    {
        $spreadsheet = IOFactory::load($absolutePath);
        $writer = new SpreadsheetHtmlWriter($spreadsheet);
        $writer->setSheetIndex(0);
        $writer->setPreCalculateFormulas(false);
        ob_start();
        $writer->save('php://output');
        return (string) ob_get_clean();
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

    /**
     * @return array{usuarios: \Illuminate\Support\Collection, usuariosJs: \Illuminate\Support\Collection, hvChips: array, panelUserId: int|null}
     */
    private function inspectionPanelData(Equipo $equipo): array
    {
        $empresaId = $this->resolveTenantEmpresaId();
        $authId = auth()->id();
        $usuarios = User::query()
            ->with('cargo:id,name')
            ->where('active', true)
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->orderBy('name')
            ->get(['id', 'name', 'last_name', 'email', 'phone', 'corporate_phone', 'photo', 'signature', 'cargo_id', 'empresa_id']);

        if ($authId && !$usuarios->firstWhere('id', $authId)) {
            $self = User::query()
                ->with('cargo:id,name')
                ->whereKey($authId)
                ->first(['id', 'name', 'last_name', 'email', 'phone', 'corporate_phone', 'photo', 'signature', 'cargo_id', 'empresa_id']);
            if ($self) {
                $usuarios = $usuarios->prepend($self);
            }
        }

        $usuariosJs = $usuarios->map(fn ($u) => [
            'id' => (int) $u->id,
            'name' => trim(($u->name ?? '') . ' ' . ($u->last_name ?? '')),
            'email' => (string) ($u->email ?? ''),
            'phone' => (string) ($u->phone ?: $u->corporate_phone ?: ''),
            'cargo' => (string) ($u->cargo?->name ?? ''),
            'photo' => $u->photo ? asset('storage/' . $u->photo) : null,
            'signature' => $u->signature ? asset('storage/' . $u->signature) : null,
        ])->values();

        return [
            'usuarios' => $usuarios,
            'usuariosJs' => $usuariosJs,
            'hvChips' => HojaVidaAutoFields::chipsForEquipo($equipo),
            'panelUserId' => auth()->id(),
        ];
    }

    private function buildAutoFields(Equipo $equipo): array
    {
        return HojaVidaAutoFields::forEquipo($equipo);
    }

    private function cleanTemplateArtifacts(string $html): string
    {
        $html = str_replace(['#REF!', '#VALUE!', '#NAME?', '#DIV/0!'], '', $html);
        return (string) preg_replace('/\s*=\s*[A-Z0-9_\.]+\([^<]{0,500}\)/i', '', $html);
    }

    /**
     * Genera el HTML completo para el PDF: conserva estilos del Excel, imágenes en base64,
     * sin saltos de página forzados, márgenes reducidos.
     */
    private function buildPdfHtml(EquipoInspeccion $inspeccion): string
    {
        $inspeccion->loadMissing(['inspector']);
        $html = (string) $inspeccion->edited_html;
        $html = $this->convertStorageImagesForPdf($html);

        // Extraer body y estilos preservando la estructura
        $body = $html;
        $styles = '';
        if (stripos($html, '<body') !== false && preg_match('/<body[^>]*>(.*)<\/body>/is', $html, $m)) {
            $body = trim((string) ($m[1] ?? $html));
        }
        if (preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $html, $styleMatches)) {
            $styles = implode("\n", array_map('trim', $styleMatches[1]));
        }

        // Compactar solo: márgenes de página y eliminar saltos forzados (generan hojas vacías)
        $styles = (string) preg_replace('/@page[^{]*\{[^}]*\}/is', '@page{margin:6mm;}', $styles);
        $styles = (string) preg_replace('/page-break-before:\s*always[^;}]*/i', 'page-break-before:auto', $styles);
        $styles = (string) preg_replace('/page-break-after:\s*always[^;}]*/i', 'page-break-after:auto', $styles);

        $body = (string) preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $body);
        $body = (string) preg_replace('/<meta\b[^>]*>/i', '', $body);
        $body = (string) preg_replace('/<style[^>]*>.*?<\/style>/is', '', $body);
        $body = $this->removePlaceholderContentFromPdf($body);
        $body = trim($body);

        $body .= $this->buildInspectorUsuariosTableHtml($inspeccion, true);

        $overrideCss = '@page{margin:6mm !important;}'
            . ' body{padding:0 4px !important;margin:0 !important;}'
            . ' .scrpgbrk,div+div{page-break-before:auto !important;margin-top:2px !important;}'
            . ' .navigation{page-break-after:auto !important;}'
            . ' img{max-width:100%;height:auto;}'
            . ' *{-webkit-print-color-adjust:exact;print-color-adjust:exact;}';

        return '<!DOCTYPE html><html><head><meta charset="utf-8">'
            . '<style>' . $overrideCss . '</style>'
            . '<style>' . $styles . '</style>'
            . '</head><body>' . $body . '</body></html>';
    }

    /**
     * Reemplaza placeholders (na, xxx, puntos, fórmulas Excel) por vacío en celdas de tabla.
     */
    private function removePlaceholderContentFromPdf(string $html): string
    {
        // Fórmulas Excel como ='[1]Listado Arnes'!D1
        $html = (string) preg_replace('/=\'\[[^\]]+\][^\']*\'![A-Z0-9]+/i', '', $html);

        // En celdas td/th: vaciar contenido que sea solo placeholder
        $html = (string) preg_replace_callback(
            '/(<(?:td|th)[^>]*>)(.*?)(<\/(?:td|th)>)/is',
            function ($m) {
                $content = $m[2];
                $content = str_replace(["\xc2\xa0", '&nbsp;'], ' ', $content);
                $test = trim(strip_tags($content));
                if ($test === 'na' || $test === 'xxx' || $test === '.') {
                    return $m[1] . '&nbsp;' . $m[3];
                }
                if (preg_match('/^x+$/i', $test) && strlen($test) >= 4) {
                    return $m[1] . '&nbsp;' . $m[3];
                }
                return $m[0];
            },
            $html
        );

        return $html;
    }

    private function sanitizeHtmlForPdf(string $html): string
    {
        return HtmlSanitizer::sanitizeUserHtml($html);
    }

    private function convertStorageImagesForPdf(string $html): string
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        if (!str_contains($html, '<img') && !str_contains($html, 'storage/')) {
            return $html;
        }
        if (str_contains($html, 'storage/') || ($appUrl !== '' && str_contains($html, $appUrl . '/storage/'))) {
            $pattern = '/\b(?:' . preg_quote($appUrl, '/') . '\/storage\/|\/storage\/|storage\/)([^\s<\"\']+\.(?:png|jpe?g|webp|gif))\b/i';
            $replaced = preg_replace_callback($pattern, function ($m) {
                $rel = ltrim((string) $m[1], '/');
                $dataUri = $this->storagePathToDataUri($rel);
                return $dataUri ?? $m[0];
            }, $html);
            if ($replaced !== null) {
                $html = (string) $replaced;
            }
        }
        return $html;
    }

    private function storagePathToDataUri(string $relativePath): ?string
    {
        return \App\Support\SafeStoragePath::toDataUri($relativePath);
    }

    private function wrapHtmlForPdf(string $body, string $extraStyles = '', ?EquipoInspeccion $inspeccion = null, bool $forPdf = true): string
    {
        if ($inspeccion) {
            $body .= $this->buildInspectorUsuariosTableHtml($inspeccion, $forPdf);
        }
        $css = '@page{margin:4mm;}'
            . ' html,body{font-family:DejaVu Sans,sans-serif;font-size:9px;margin:0;padding:1px;color:#333;line-height:1.1;-webkit-print-color-adjust:exact;print-color-adjust:exact;}'
            . ' *{-webkit-print-color-adjust:exact;print-color-adjust:exact;}'
            . ' table{width:100%;border-collapse:collapse;margin-bottom:1px;}'
            . ' td,th{border:1px solid #666;padding:1px 3px;vertical-align:top;}'
            . ' th{background:#00AAE6 !important;color:#fff !important;font-weight:bold;}'
            . ' .tbl-header th{background:#00B0F0 !important;color:#fff !important;}'
            . ' img{max-width:100%;height:auto;display:inline-block;vertical-align:middle;}'
            . ' .inspeccion-tabla-datos td,.inspeccion-tabla-datos th{font-size:8px;padding:1px 2px;}'
            . ' .inspeccion-tabla-datos th{background:#00AAE6 !important;color:#fff !important;}'
            . ' .scrpgbrk,div+div{page-break-before:auto !important;margin-top:1px !important;}'
            . ' .navigation{page-break-after:auto !important;}'
            . ' tr{height:auto !important;min-height:0 !important;}';
        $headStyles = '<style>' . $css . '</style>';
        if ($extraStyles !== '') {
            $headStyles .= '<style>' . $extraStyles . '</style>';
        }
        $headStyles .= '<style>@page{margin:4mm !important;} html,body{padding:1px !important;margin:0 !important;} table{margin-bottom:1px !important;} td,th{padding:1px 2px !important;} .scrpgbrk,div+div{page-break-before:auto !important;margin-top:0 !important;} .navigation{page-break-after:auto !important;} tr{height:auto !important;}</style>';
        return '<!DOCTYPE html><html><head><meta charset="utf-8">' . $headStyles . '</head><body>' . $body . '</body></html>';
    }

    /**
     * Para vista previa: asset(). Para PDF: base64 (DomPDF lo soporta mejor que file://).
     */
    private function storagePathToImageSrc(string $path, bool $forPdf): ?string
    {
        $relative = ltrim($path, '/');
        if (!Storage::disk('public')->exists($relative)) {
            return null;
        }
        if (!$forPdf) {
            return asset('storage/' . $relative);
        }
        return $this->storagePathToDataUri($relative);
    }

    private function buildInspectorUsuariosTableHtml(EquipoInspeccion $inspeccion, bool $forPdf = true): string
    {
        $inspector = $inspeccion->inspector;
        $userIds = $inspeccion->selected_user_ids ?? [];
        $usuarios = collect();
        if (!empty($userIds)) {
            $byId = User::query()->whereIn('id', $userIds)->get(['id', 'name', 'last_name', 'photo'])->keyBy('id');
            foreach ($userIds as $id) {
                if ($byId->has($id)) {
                    $usuarios->push($byId->get($id));
                }
            }
        }

        $inspectorFoto = '';
        $inspectorFirma = '';
        if ($inspector) {
            if ($inspector->photo) {
                $url = $this->storagePathToImageSrc($inspector->photo, $forPdf);
                $inspectorFoto = $url ? '<img src="' . htmlspecialchars($url) . '" width="50" height="50">' : '';
            }
            if ($inspector->signature) {
                $url = $this->storagePathToImageSrc($inspector->signature, $forPdf);
                $inspectorFirma = $url ? '<img src="' . htmlspecialchars($url) . '" width="80" height="40">' : '';
            }
        }

        $html = '<div style="margin-top:2px;"><table class="inspeccion-tabla-datos" style="width:100%;border-collapse:collapse;">';
        $html .= '<thead class="tbl-header"><tr><th style="border:1px solid #666;padding:3px 4px;">INSPECTOR</th><th style="border:1px solid #666;padding:3px 4px;">FOTO</th><th style="border:1px solid #666;padding:3px 4px;">FIRMA</th></tr></thead>';
        $html .= '<tr>';
        $html .= '<td style="border:1px solid #666;padding:3px 4px;">' . ($inspector ? e(trim(($inspector->name ?? '') . ' ' . ($inspector->last_name ?? ''))) : '—') . '</td>';
        $html .= '<td style="border:1px solid #666;padding:3px 4px;">' . ($inspectorFoto ?: '—') . '</td>';
        $html .= '<td style="border:1px solid #666;padding:3px 4px;">' . ($inspectorFirma ?: '—') . '</td>';
        $html .= '</tr></table>';

        if ($usuarios->isNotEmpty()) {
            $html .= '<table class="inspeccion-tabla-datos" style="width:100%;border-collapse:collapse;margin-top:2px;">';
            $html .= '<thead class="tbl-header"><tr><th style="border:1px solid #666;padding:3px 4px;">#</th><th style="border:1px solid #666;padding:3px 4px;">USUARIO</th><th style="border:1px solid #666;padding:3px 4px;">FOTO</th></tr></thead>';
            foreach ($usuarios as $i => $u) {
                $uFoto = '';
                if (!empty($u->photo)) {
                    $url = $this->storagePathToImageSrc($u->photo, $forPdf);
                    $uFoto = $url ? '<img src="' . htmlspecialchars($url) . '" width="40" height="40">' : '';
                }
                $html .= '<tr>';
                $html .= '<td style="border:1px solid #666;padding:3px 4px;">' . ($i + 1) . '</td>';
                $html .= '<td style="border:1px solid #666;padding:3px 4px;">' . e(trim(($u->name ?? '') . ' ' . ($u->last_name ?? ''))) . '</td>';
                $html .= '<td style="border:1px solid #666;padding:3px 4px;">' . ($uFoto ?: '—') . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table><div style="font-size:7px;margin-top:2px;color:#666;">USUARIOS (CANTIDAD: ' . $usuarios->count() . ')</div>';
        }
        $html .= '</div>';

        return $html;
    }

    private function compactPdfStyles(string $styles): string
    {
        $styles = (string) preg_replace('/@page\s*\{[^}]*\}/is', '@page{margin:4mm;}', $styles);
        $styles = (string) preg_replace('/(margin(?:-[a-z]+)?):\s*[\d.]+\s*in/i', '$1:2mm', $styles);
        $styles = (string) preg_replace('/(padding(?:-[a-z]+)?):\s*[\d.]+\s*in/i', '$1:1mm', $styles);
        // Eliminar saltos de página forzados que generan espacios vacíos
        $styles = (string) preg_replace('/page-break-before:\s*always[^;}]*/i', 'page-break-before:auto', $styles);
        $styles = (string) preg_replace('/page-break-after:\s*always[^;}]*/i', 'page-break-after:auto', $styles);
        // Reducir alturas de filas fijas del Excel para compactar
        $styles = (string) preg_replace('/\bheight:\s*[\d.]+\s*pt\b/i', 'height:auto', $styles);
        return $styles;
    }

    /**
     * Extrae solo el contenido del body y los estilos (igual que Hoja de Vida).
     */
    private function extractBodyAndStylesForPdf(string $html): array
    {
        $body = $html;
        $styles = '';

        if (stripos($html, '<body') !== false && preg_match('/<body[^>]*>(.*)<\/body>/is', $html, $m)) {
            $body = trim((string) ($m[1] ?? $html));
        }

        if (preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $html, $styleMatches)) {
            $styles = implode("\n", array_map('trim', $styleMatches[1]));
        }
        $styles = $this->compactPdfStyles($styles);

        $body = (string) preg_replace('/<style[^>]*>.*?<\/style>/is', '', $body);
        $body = trim($body);

        return ['body' => $body, 'styles' => $styles];
    }

    /**
     * Elimina celdas de tabla vacías para la vista previa (igual que Hoja de Vida).
     */
    private function removeEmptyTableCells(string $html): string
    {
        if (!class_exists(\DOMDocument::class)) {
            return $html;
        }

        $prev = libxml_use_internal_errors(true);
        try {
            $dom = new \DOMDocument();
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $xpath = new \DOMXPath($dom);

            $cells = $xpath->query('//td | //th');
            $toRemove = [];
            foreach ($cells as $cell) {
                if ($cell instanceof \DOMElement && !$this->cellHasContent($cell)) {
                    $toRemove[] = $cell;
                }
            }

            foreach ($toRemove as $cell) {
                if ($cell->parentNode) {
                    $cell->parentNode->removeChild($cell);
                }
            }

            $out = $dom->saveHTML();
            return is_string($out) && $out !== '' ? $out : $html;
        } catch (\Throwable $e) {
            return $html;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($prev);
        }
    }

    private function cellHasContent(\DOMElement $cell): bool
    {
        $rawText = (string) $cell->textContent;
        $rawText = str_replace("\xc2\xa0", ' ', $rawText);
        $text = trim($rawText);
        if ($text !== '') {
            return true;
        }
        if ($cell->getElementsByTagName('img')->length > 0) {
            return true;
        }
        $style = strtolower((string) $cell->getAttribute('style'));
        if ($style !== '' && str_contains($style, 'background')) {
            return true;
        }
        return false;
    }
}
