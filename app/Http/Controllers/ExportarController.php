<?php

namespace App\Http\Controllers;

use App\Models\ClaseEquipo;
use App\Models\Equipo;
use App\Models\EquipoBaja;
use App\Models\EquipoImagen;
use App\Models\EquipoInspeccion;
use App\Models\HojaVidaDocumento;
use App\Models\HojaVidaPlantilla;
use App\Models\User;
use App\Support\HtmlSanitizer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Html as SpreadsheetHtmlWriter;

class ExportarController extends Controller
{
    private function assertEquipoTenant(Equipo $equipo): void
    {
        if ($equipo->empresa_id) {
            $this->moduleAuthz()->assertTenantOwns((int) $equipo->empresa_id);
        }
    }

    public function index(Request $request)
    {
        $this->assertCanViewModule('exportar');
        $empresaId = $this->resolveTenantEmpresaId();
        $query = Equipo::query()
            ->with(['imagenes', 'claseEquipo', 'tipoEquipo'])
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId));

        $filterOrigen = $request->query('origen', '');
        if ($filterOrigen === 'inventario') {
            $query->where(function ($q) {
                $q->whereNull('codigo')->orWhere('codigo', 'like', 'IN-%');
            });
        } elseif ($filterOrigen === 'baja') {
            $query->where('codigo', 'like', 'DB-%');
        } elseif ($filterOrigen === 'auditoria') {
            $query->where('codigo', 'like', 'AUD-%');
        } elseif ($filterOrigen === 'material-didactico') {
            $query->where('codigo', 'like', 'MD-%');
        }

        $filterClase = $request->query('clase', '');
        if ($filterClase && ctype_digit($filterClase)) {
            $query->where('clase_equipo_id', (int) $filterClase);
        }

        $filterBuscar = trim((string) $request->query('buscar', ''));
        if ($filterBuscar !== '') {
            $q = '%' . $filterBuscar . '%';
            $query->where(function ($qb) use ($q) {
                $qb->where('codigo', 'like', $q)
                    ->orWhere('nombre', 'like', $q)
                    ->orWhere('serial', 'like', $q)
                    ->orWhere('descripcion', 'like', $q);
            });
        }

        $equipos = $query
            ->orderByRaw("CASE WHEN codigo LIKE 'IN-%' THEN CAST(SUBSTRING(codigo,4) AS UNSIGNED) END ASC")
            ->orderBy('codigo')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $equipoIds = $equipos->getCollection()->pluck('id');
        $docsByEquipo = HojaVidaDocumento::query()
            ->whereIn('equipo_id', $equipoIds)
            ->get()
            ->keyBy('equipo_id');

        $ultimaInspeccionByEquipo = EquipoInspeccion::query()
            ->whereIn('equipo_id', $equipoIds)
            ->orderByDesc('validez_hasta')
            ->get()
            ->unique('equipo_id')
            ->keyBy('equipo_id');

        $bajaByEquipo = EquipoBaja::query()
            ->whereIn('equipo_id', $equipoIds)
            ->orderByDesc('id')
            ->get()
            ->unique('equipo_id')
            ->keyBy('equipo_id');

        $clases = ClaseEquipo::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return view('admin.exportar.index', compact(
            'equipos',
            'docsByEquipo',
            'ultimaInspeccionByEquipo',
            'bajaByEquipo',
            'clases',
            'filterOrigen',
            'filterClase',
            'filterBuscar'
        ));
    }

    public function actualizar(Request $request)
    {
        $this->assertCanEditModule('exportar');
        if (\Illuminate\Support\Facades\Schema::hasColumn('equipos', 'hoja_vida_completa_html')) {
            Equipo::query()->update(['hoja_vida_completa_html' => null]);
        }
        return redirect()->route('exportar.index')
            ->with('success', 'Datos actualizados. Las descargas usarán la última inspección, hoja de vida y formato de baja.');
    }

    public function downloadHojaVida(Equipo $equipo)
    {
        $this->assertCanViewModule('exportar');
        $this->assertEquipoTenant($equipo);
        $clase = $equipo->claseEquipo;
        if (!$clase) {
            return redirect()->route('exportar.index')->with('error', 'Equipo sin clase asignada.');
        }
        $url = route('hoja-vida.pdf', [$clase, $equipo]) . '?async=0&download=1';
        return redirect($url);
    }

    public function downloadInspeccion(Equipo $equipo)
    {
        $this->assertCanViewModule('exportar');
        $this->assertEquipoTenant($equipo);
        $inspeccion = $equipo->inspecciones()->orderByDesc('validez_hasta')->first();
        if (!$inspeccion) {
            return redirect()->route('exportar.index')->with('error', 'No hay inspección para este equipo.');
        }
        if (!$inspeccion->pdf_path || !Storage::disk('public')->exists($inspeccion->pdf_path)) {
            return redirect()->route('exportar.index')->with('error', 'El PDF de inspección aún no está generado.');
        }
        $nombre = 'inspeccion-' . ($equipo->codigo ?: $equipo->id) . '.pdf';
        return Storage::disk('public')->download($inspeccion->pdf_path, $nombre);
    }

    public function downloadBaja(Equipo $equipo)
    {
        $this->assertCanViewModule('exportar');
        $this->assertEquipoTenant($equipo);
        $baja = EquipoBaja::query()->where('equipo_id', $equipo->id)->orderByDesc('id')->first();
        if (!$baja) {
            return redirect()->route('exportar.index')->with('error', 'No hay formato de baja para este equipo.');
        }
        if (!$baja->pdf_path || !Storage::disk('public')->exists($baja->pdf_path)) {
            return redirect()->route('exportar.index')->with('error', 'El PDF de baja aún no está disponible.');
        }
        return Storage::disk('public')->download($baja->pdf_path, $baja->codigo_db . '.pdf');
    }

    public function previewHojaVidaCompleta(Equipo $equipo)
    {
        $this->assertCanViewModule('exportar');
        $this->assertEquipoTenant($equipo);
        $result = $this->buildHojaVidaCompletaContent($equipo);
        if (!$result) {
            return redirect()->route('exportar.index')->with('error', 'No hay contenido para generar la hoja de vida completa.');
        }
        return view('admin.exportar.hoja_vida_completa_preview', [
            'equipo' => $equipo,
            'previewBody' => $result['previewBody'] ?? $result['fullHtml'],
            'docCss' => $result['docCss'] ?? '',
        ]);
    }

    public function downloadHojaVidaCompletaWithHtml(Request $request, Equipo $equipo)
    {
        $this->assertCanViewModule('exportar');
        $this->assertEquipoTenant($equipo);
        $request->validate(['edited_html' => ['required', 'string', 'max:5000000']]);
        $body = (string) $request->input('edited_html');
        $fullHtml = $this->wrapHtmlForPdf($body);

        $pdf = Pdf::setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
        ])
            ->loadHTML($fullHtml)
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', 8)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('margin-right', 10);

        $filename = 'hoja_vida_completa_' . ($equipo->codigo ?: $equipo->id) . '.pdf';
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function guardarHojaVidaFromPreview(Request $request, Equipo $equipo)
    {
        $this->assertCanEditModule('exportar');
        $this->assertEquipoTenant($equipo);
        $request->validate(['edited_html' => ['required', 'string', 'max:5000000']]);
        $fullBody = HtmlSanitizer::sanitizeTemplateHtml((string) $request->input('edited_html'));

        $doc = HojaVidaDocumento::query()->where('equipo_id', $equipo->id)->first();
        if (!$doc) {
            return redirect()->route('exportar.hoja-vida-completa.preview', $equipo)
                ->with('error', 'No hay hoja de vida para este equipo.');
        }

        $sectionHtml = $this->extractHojaVidaSectionHtml($fullBody);

        if ($sectionHtml !== null) {
            $doc->update(['edited_html' => $sectionHtml, 'actualizado_por' => auth()->id()]);
            return redirect()->route('exportar.hoja-vida-completa.preview', $equipo)
                ->with('success', 'Cambios guardados en Hoja de Vida. También se reflejan en el formulario de Hoja de Vida.');
        }

        return redirect()->route('exportar.hoja-vida-completa.preview', $equipo)
            ->with('error', 'No se pudo extraer la sección Hoja de Vida.');
    }

    public function downloadHojaVidaCompleta(Equipo $equipo)
    {
        $this->assertCanViewModule('exportar');
        $this->assertEquipoTenant($equipo);
        @ini_set('max_execution_time', 120);
        @set_time_limit(120);
        @ini_set('memory_limit', '512M');

        $result = $this->buildHojaVidaCompletaContent($equipo);
        if (!$result) {
            return redirect()->route('exportar.index')->with('error', 'No hay contenido para generar la hoja de vida completa.');
        }

        $fullHtml = $result['fullHtml'];
        $pdf = Pdf::setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
        ])
            ->loadHTML($fullHtml)
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', 8)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('margin-right', 10);

        $filename = 'hoja_vida_completa_' . ($equipo->codigo ?: $equipo->id) . '.pdf';
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
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

    private function convertStorageImagesForPdf(string $html): string
    {
        return (string) preg_replace_callback(
            '#src=["\'](?:/storage/|' . preg_quote(asset('storage/'), '#') . ')([^"\']+)["\']#',
            function ($m) {
                $rel = $m[1];
                $path = Storage::disk('public')->path($rel);
                if (file_exists($path)) {
                    $b64 = base64_encode(file_get_contents($path));
                    $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION)) ?: 'jpg';
                    $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';
                    return 'src="data:' . $mime . ';base64,' . $b64 . '"';
                }
                return $m[0];
            },
            $html
        );
    }

    private function storagePathToDataUri(string $path): ?string
    {
        $relative = ltrim($path, '/');
        if (!Storage::disk('public')->exists($relative)) {
            return null;
        }
        $abs = Storage::disk('public')->path($relative);
        if (!is_file($abs) || !is_readable($abs)) {
            return null;
        }
        $mime = @mime_content_type($abs) ?: 'image/jpeg';
        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($abs));
    }

    private function applyPdfMediaTokens(string $html, $imagenes, ?User $user): string
    {
        for ($i = 1; $i <= 6; $i++) {
            $token = '{{IMAGEN_' . $i . '}}';
            $img = isset($imagenes[$i - 1]) ? $imagenes[$i - 1] : null;
            if ($img && $img->path) {
                $dataUri = $this->storagePathToDataUri($img->path);
                $html = str_replace($token, $dataUri ? '<img src="' . $dataUri . '" style="max-width:200px;max-height:200px;">' : '', $html);
            } else {
                $html = str_replace($token, '', $html);
            }
        }
        $firmaHtml = '';
        $fotoHtml = '';
        if ($user) {
            if ($user->signature) {
                $dataUri = $this->storagePathToDataUri($user->signature);
                if ($dataUri) {
                    $firmaHtml = '<img src="' . $dataUri . '" style="max-width:200px;max-height:100px;">';
                }
            }
            if ($user->photo) {
                $dataUri = $this->storagePathToDataUri($user->photo);
                if ($dataUri) {
                    $fotoHtml = '<img src="' . $dataUri . '" style="max-width:100px;max-height:100px;">';
                }
            }
        }
        $html = str_replace('{{FIRMA_USUARIO}}', $firmaHtml, $html);
        $html = str_replace('{{FOTO_USUARIO}}', $fotoHtml, $html);
        return $html;
    }

    private function isCellEmptyOrPlaceholder(string $text): bool
    {
        $t = trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $t = preg_replace('/\s+/', ' ', $t);
        if ($t === '' || $t === '—' || $t === '-' || $t === '&nbsp;') {
            return true;
        }
        if (preg_match('/^X{4,}$/i', $t) || preg_match('/^[X\s]{6,}$/i', $t)) {
            return true;
        }
        if (preg_match('/^=\s*[\'"]/', $t) || preg_match('/^=\'\[/', $t)) {
            return true;
        }
        return false;
    }

    private function removeEmptyFieldsFromHtml(string $html): string
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $tables = $dom->getElementsByTagName('table');
        foreach ($tables as $table) {
            $rows = $table->getElementsByTagName('tr');
            $toRemove = [];
            for ($i = 0; $i < $rows->length; $i++) {
                $tr = $rows->item($i);
                $tds = $tr->getElementsByTagName('td');
                if ($tds->length === 2) {
                    $valueCell = $tds->item(1);
                    $valueHtml = $dom->saveHTML($valueCell);
                    if ($this->isCellEmptyOrPlaceholder($valueHtml)) {
                        $toRemove[] = $tr;
                    }
                }
            }
            foreach ($toRemove as $tr) {
                $tr->parentNode->removeChild($tr);
            }
        }

        $this->hideEmptyCellsInDom($dom);

        $body = $dom->getElementsByTagName('div')->item(0);
        return $body ? $dom->saveHTML($body) : $html;
    }

    private function removeEmptyTablesFromHtml(string $html): string
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $tables = [];
        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//table') as $table) {
            $tables[] = $table;
        }
        foreach ($tables as $table) {
            $allRowsEmpty = true;
            foreach ($table->getElementsByTagName('tr') as $tr) {
                foreach ($tr->getElementsByTagName('td') as $td) {
                    $text = trim(html_entity_decode(strip_tags($dom->saveHTML($td)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    if ($text !== '' && !preg_match('/^X{4,}$/i', $text) && !preg_match('/^=\'\[/', $text)) {
                        $allRowsEmpty = false;
                        break 2;
                    }
                }
            }
            if ($allRowsEmpty) {
                $table->parentNode->removeChild($table);
            }
        }

        $body = $dom->getElementsByTagName('div')->item(0);
        return $body ? $dom->saveHTML($body) : $html;
    }

    private function removeEmptyColumnsFromHtml(string $html): string
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        foreach ($dom->getElementsByTagName('table') as $table) {
            $rows = iterator_to_array($table->getElementsByTagName('tr'));
            if (empty($rows)) {
                continue;
            }
            $maxCols = 0;
            foreach ($rows as $tr) {
                $count = 0;
                foreach ($tr->childNodes as $n) {
                    if ($n->nodeName === 'td' || $n->nodeName === 'th') {
                        $count++;
                    }
                }
                $maxCols = max($maxCols, $count);
            }
            $colsToRemove = [];
            for ($col = $maxCols - 1; $col >= 0; $col--) {
                $allEmpty = true;
                foreach ($rows as $tr) {
                    $cells = [];
                    foreach ($tr->childNodes as $n) {
                        if ($n->nodeName === 'td' || $n->nodeName === 'th') {
                            $cells[] = $n;
                        }
                    }
                    if (isset($cells[$col])) {
                        $text = trim(html_entity_decode(strip_tags($dom->saveHTML($cells[$col])), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                        if ($text !== '' && !preg_match('/^X{4,}$/i', $text)) {
                            $allEmpty = false;
                            break;
                        }
                    }
                }
                if ($allEmpty) {
                    $colsToRemove[] = $col;
                }
            }
            foreach ($colsToRemove as $col) {
                foreach ($rows as $tr) {
                    $cells = [];
                    foreach ($tr->childNodes as $node) {
                        if ($node->nodeName === 'td' || $node->nodeName === 'th') {
                            $cells[] = $node;
                        }
                    }
                    if (isset($cells[$col])) {
                        $cells[$col]->parentNode->removeChild($cells[$col]);
                    }
                }
            }
        }

        $body = $dom->getElementsByTagName('div')->item(0);
        return $body ? $dom->saveHTML($body) : $html;
    }

    private function hideEmptyCellsInDom(\DOMDocument $dom): void
    {
        $cells = [];
        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//td | //th') as $cell) {
            $cells[] = $cell;
        }
        foreach ($cells as $cell) {
            $html = $dom->saveHTML($cell);
            if ($this->isCellEmptyOrPlaceholder($html)) {
                while ($cell->firstChild) {
                    $cell->removeChild($cell->firstChild);
                }
                $cell->setAttribute('class', trim(($cell->getAttribute('class') ?: '') . ' doc-empty-cell'));
            }
        }
    }

    private function stripDarkBackgrounds(string $html): string
    {
        $html = (string) preg_replace('/background(?:-color)?\s*:\s*(black|navy|#0{3,8}|#1[0-9a-fA-F]{2}|#2[0-5][0-9a-fA-F]|#333|#222|#111)\s*;?/i', 'background-color:#fff;', $html);
        $html = (string) preg_replace_callback('/background(?:-color)?\s*:\s*rgb\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)\s*;?/i', function ($m) {
            $r = (int) $m[1];
            $g = (int) $m[2];
            $b = (int) $m[3];
            if ($r < 80 && $g < 80 && $b < 80) {
                return 'background-color:#fff;';
            }
            return $m[0];
        }, $html);
        return $html;
    }

    private function getDocCss(): string
    {
        return <<<'CSS'
html,body{font-family:DejaVu Sans,sans-serif;font-size:8px;line-height:1.2;margin:0;padding:0;color:#222;background:#fff;}
.doc-header{background:linear-gradient(135deg,#1e3a5f 0%,#2d5a87 100%);color:#fff;padding:8px 12px;margin-bottom:8px;text-align:center;}
.doc-title{font-size:12px;font-weight:bold;letter-spacing:0.5px;}
.doc-subtitle{font-size:9px;margin-top:2px;opacity:0.95;}
.doc-body{padding:0 10px 10px;}
.doc-seccion{background:#fff;border:1px solid #e0e0e0;border-radius:3px;margin-bottom:8px;overflow:hidden;}
.doc-seccion-title{background:#f5f7fa;color:#1e3a5f;font-size:9px;font-weight:bold;padding:4px 8px;border-bottom:1px solid #e0e0e0;}
.doc-seccion-body{padding:6px 8px;}
.doc-seccion-body table{width:100%;border-collapse:collapse;margin:0;empty-cells:show;}
.doc-seccion-body td,.doc-seccion-body th{border:1px solid #ccc;padding:3px 4px;vertical-align:top;}
.doc-seccion-body th{background:#f0f4f8;font-weight:bold;}
.seccion-tabla{width:100%;border-collapse:collapse;margin:0;empty-cells:show;}
.seccion-tabla td,.seccion-tabla th{border:1px solid #ccc;padding:4px 5px;vertical-align:middle;}
.seccion-tabla th{background:#f0f4f8;font-weight:bold;}
.fotos-table .foto-label{width:90px;font-weight:500;}
.fotos-table .foto-img{max-width:110px;max-height:80px;display:block;}
img{max-width:100%;height:auto;}
table{empty-cells:show;}
CSS;
    }

    private function wrapHtmlForPdf(string $body): string
    {
        // Quitar saltos de página forzados que puedan venir de Excel/plantillas
        $body = (string) preg_replace('/page-break-(before|after|inside)\s*:[^;"]*;?/i', '', $body);
        $body = (string) preg_replace_callback(
            '/style="[^"]*?page-break-(before|after|inside)\s*:[^";]*;?[^"]*?"/i',
            function (array $m): string {
                // Limpia solo las reglas page-break dentro del style
                $clean = (string) preg_replace(
                    '/page-break-(before|after|inside)\s*:[^;"]*;?/i',
                    '',
                    $m[0]
                );
                // Elimina style vacío
                return trim($clean) === 'style=""' ? '' : $clean;
            },
            $body
        );

        $css = $this->getDocCss();
        return '<!DOCTYPE html><html><head><meta charset="utf-8"><style>' . $css . '</style></head><body>' . $body . '</body></html>';
    }

    private function buildHojaVidaCompletaContent(Equipo $equipo): ?array
    {
        $equipo->loadMissing(['empresa', 'imagenes']);
        $clase = $equipo->claseEquipo;
        $secciones = [];

        $doc = HojaVidaDocumento::query()->where('equipo_id', $equipo->id)->first();
        $htmlHv = '';
        if ($doc && strlen(trim(strip_tags($doc->edited_html ?? ''))) > 0) {
            $imagenes = [];
            $selectedIds = array_values(array_filter(array_map('intval', (array) ($doc->selected_equipo_imagen_ids ?? []))));
            if (!empty($selectedIds)) {
                $imagenes = EquipoImagen::query()
                    ->where('equipo_id', $equipo->id)
                    ->whereIn('id', $selectedIds)
                    ->orderBy('id')
                    ->get();
            }
            $user = $doc->signature_user_id ? User::query()->find($doc->signature_user_id) : null;
            $htmlHv = $this->applyPdfMediaTokens((string) $doc->edited_html, $imagenes, $user);
            $htmlHv = $this->convertStorageImagesForPdf($htmlHv);
        } elseif ($clase) {
            $plantilla = HojaVidaPlantilla::query()->where('clase_equipo_id', $clase->id)->first();
            if ($plantilla && Storage::disk('public')->exists($plantilla->plantilla_excel_path)) {
                try {
                    $templateHtml = $this->excelToHtml(Storage::disk('public')->path($plantilla->plantilla_excel_path));
                    $defaults = $this->buildAutoFields($equipo);
                    foreach ($defaults as $key => $value) {
                        $token = '{{' . strtoupper((string) $key) . '}}';
                        $templateHtml = str_replace($token, e((string) $value), $templateHtml);
                    }
                    $templateHtml = (string) preg_replace('/\{\{\s*[A-Z0-9_\-]+\s*\}\}/', '', $templateHtml);
                    $htmlHv = $this->convertStorageImagesForPdf($templateHtml);
        } catch (\Throwable $e) {
                    $htmlHv = '<p>Hoja de vida no completada.</p>';
                }
            }
        }
        if ($htmlHv) {
            $secciones[] = '<div class="doc-seccion"><div class="doc-seccion-title">Hoja de Vida</div><div class="doc-seccion-body">' . $htmlHv . '</div></div>';
        }

        $imagenes = $equipo->imagenes()->orderBy('id')->get();
        if ($imagenes->isNotEmpty()) {
            $filas = [];
            $labels = ['general' => 'Foto general', 'etiqueta' => 'Foto etiqueta', 'certificacion_evidencia' => 'Certificación', 'kit_general' => 'Kit'];
            foreach ($imagenes as $img) {
                $label = $labels[$img->tipo] ?? $img->tipo;
                $dataUri = $this->storagePathToDataUri($img->path);
                if ($dataUri) {
                    $filas[] = '<tr><td class="foto-label">' . e($label) . '</td><td><img src="' . $dataUri . '" class="foto-img"/></td></tr>';
                }
            }
            if (!empty($filas)) {
                $tabla = '<table class="seccion-tabla fotos-table"><thead><tr><th>Tipo</th><th>Imagen</th></tr></thead><tbody>' . implode('', $filas) . '</tbody></table>';
                $secciones[] = '<div class="doc-seccion"><div class="doc-seccion-title">Fotos del equipo</div>' . $tabla . '</div>';
            }
        }

        $ultimaInspeccion = $equipo->inspecciones()->orderByDesc('validez_hasta')->first();
        if ($ultimaInspeccion && $ultimaInspeccion->edited_html) {
            $htmlIns = $this->convertStorageImagesForPdf($ultimaInspeccion->edited_html);
            $secciones[] = '<div class="doc-seccion"><div class="doc-seccion-title">Última inspección</div><div class="doc-seccion-body">' . $htmlIns . '</div></div>';
        }

        $baja = EquipoBaja::query()->where('equipo_id', $equipo->id)->orderByDesc('id')->first();
        if ($baja && $baja->plantilla_excel_path && Storage::disk('public')->exists($baja->plantilla_excel_path)) {
            try {
                $templateHtml = $this->excelToHtml(Storage::disk('public')->path($baja->plantilla_excel_path));
                $formData = $this->buildBajaFormData($baja, $equipo);
                foreach ($formData as $key => $value) {
                    $token = '{{' . strtoupper((string) $key) . '}}';
                    $templateHtml = str_replace($token, e((string) $value), $templateHtml);
                }
                $templateHtml = (string) preg_replace('/\{\{\s*[A-Z0-9_\-]+\s*\}\}/', '', $templateHtml);
                $templateHtml = $this->convertStorageImagesForPdf($templateHtml);
                $templateHtml = $this->injectBajaElementosRow($templateHtml, $baja, $equipo);
                $secciones[] = '<div class="doc-seccion"><div class="doc-seccion-title">Reporte de baja</div><div class="doc-seccion-body">' . $templateHtml . '</div></div>';
            } catch (\Throwable $e) {
                //
            }
        }

        $inspectorUsuariosHtml = $this->buildInspectorUsuariosHtml($equipo);
        if ($inspectorUsuariosHtml) {
            $secciones[] = $inspectorUsuariosHtml;
        }

        if (empty($secciones)) {
            return null;
        }

        $body = implode('', $secciones);
        $body = $this->stripDarkBackgrounds($body);

        $logoHtml = '';
        $empresa = $equipo->empresa ?? $equipo->empresa()->first();
        if ($empresa && !empty($empresa->logo) && Storage::disk('public')->exists($empresa->logo)) {
            $logoUri = $this->storagePathToDataUri($empresa->logo);
            if ($logoUri) {
                $logoHtml = '<img src="' . $logoUri . '" alt="Logo" style="max-height:48px;max-width:160px;margin-bottom:8px;">';
            }
        }
        $header = '<div class="doc-header">' . $logoHtml . '<div class="doc-title">Hoja de Vida Completa</div><div class="doc-subtitle">' . e($equipo->codigo ?? $equipo->id) . ' — ' . e($equipo->nombre ?? '') . '</div></div>';
        $fullHtml = $this->wrapHtmlForPdf($header . '<div class="doc-body">' . $body . '</div>');

        $previewBody = $header . '<div class="doc-body">' . $body . '</div>';
        $docCss = $this->getDocCss();

        return ['fullHtml' => $fullHtml, 'previewBody' => $previewBody, 'docCss' => $docCss];
    }

    private function extractHojaVidaSectionHtml(string $html): ?string
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//div[contains(@class, "doc-seccion")]') as $seccion) {
            $titleDiv = $seccion->getElementsByTagName('div')->item(0);
            if ($titleDiv && trim($titleDiv->textContent ?? '') === 'Hoja de Vida') {
                $bodyDivs = $xpath->query('.//div[contains(@class, "doc-seccion-body")]', $seccion);
                if ($bodyDivs->length > 0) {
                    $bodyDiv = $bodyDivs->item(0);
                    $innerHtml = '';
                    foreach ($bodyDiv->childNodes as $child) {
                        $innerHtml .= $dom->saveHTML($child);
                    }
                    return trim($innerHtml);
                }
            }
        }
        return null;
    }

    private function buildBajaFormData(EquipoBaja $baja, Equipo $equipo): array
    {
        $equipo->loadMissing(['empresa', 'sede', 'bodega', 'fabricante', 'tipoEquipo', 'claseEquipo']);
        $base = (array) ($baja->form_data ?? []);
        $fechaFab = $equipo->fecha_fabricacion ? $equipo->fecha_fabricacion->format('Y-m-d') : '';
        $fechaCompra = $equipo->fecha_compra ? $equipo->fecha_compra->format('Y-m-d') : ($base['FECHA_COMPRA'] ?? '');
        $fechaBaja = $baja->fecha_baja ? $baja->fecha_baja->format('Y-m-d') : ($base['FECHA_BAJA'] ?? '');
        $extra = [
            'CODIGO' => (string) ($equipo->codigo ?? $baja->codigo_db ?? ''),
            'CODIGO_INTERNO' => (string) ($equipo->codigo ?? $baja->codigo_db ?? ''),
            'ACTA_NO' => (string) ($baja->codigo_db ?? ''),
            'FECHA_ACTA' => $fechaBaja,
            'FECHA' => $fechaBaja,
            'DESCRIPCION' => (string) ($equipo->descripcion ?? $equipo->nombre ?? ''),
            'DESCRIPCION_ELEMENTO' => (string) ($equipo->descripcion ?? $equipo->nombre ?? ''),
            'MARCA' => (string) ($equipo->fabricante?->name ?? ''),
            'MODELO' => (string) ($equipo->tipoEquipo?->nombre ?? ''),
            'FECHA_FABRICACION' => $fechaFab,
            'FECHA_ADQUISICION' => $fechaCompra,
            'ORIGEN' => (string) ($equipo->empresa?->nombre ?? ''),
            'PROCEDENCIA' => (string) ($equipo->empresa?->nombre ?? $equipo->sede?->nombre ?? ''),
            'DESTINO_FINAL' => (string) ($base['DESTINO_FINAL'] ?? 'Baja definitiva'),
            'CLASIFICACION' => (string) ($base['CLASIFICACION'] ?? 'Inservible'),
            'PAGADO_POR' => (string) ($base['PAGADO_POR'] ?? $equipo->empresa?->nombre ?? ''),
            'NOMBRE_ASISTENTE' => (string) ($base['NOMBRE_ASISTENTE'] ?? ''),
            'CARGO' => (string) ($base['CARGO'] ?? ''),
        ];
        return array_merge($base, $extra);
    }

    private function buildInspectorUsuariosHtml(Equipo $equipo): ?string
    {
        $inspeccion = $equipo->inspecciones()->orderByDesc('validez_hasta')->first();
        if (!$inspeccion || (!$inspeccion->inspector_user_id && empty($inspeccion->selected_user_ids))) {
            return null;
        }

        $inspector = $inspeccion->inspector_user_id ? User::query()->find($inspeccion->inspector_user_id) : null;
        $userIds = array_values(array_filter(array_map('intval', (array) ($inspeccion->selected_user_ids ?? []))));
        $usuarios = !empty($userIds) ? User::query()->whereIn('id', $userIds)->orderByRaw('FIELD(id,' . implode(',', $userIds) . ')')->get() : collect();

        $filas = [];
        if ($inspector) {
            $nombre = trim($inspector->name . ' ' . ($inspector->last_name ?? ''));
            $filas[] = '<tr><td class="foto-label">Inspector</td><td>' . e($nombre) . '</td></tr>';
            $fotoCell = '—';
            if ($inspector->photo) {
                $dataUri = $this->storagePathToDataUri($inspector->photo);
                if ($dataUri) {
                    $fotoCell = '<img src="' . $dataUri . '" class="foto-img" style="max-width:80px;max-height:80px;"/>';
                }
            }
            $filas[] = '<tr><td class="foto-label">Foto</td><td>' . $fotoCell . '</td></tr>';
            $firmaCell = '—';
            if ($inspector->signature) {
                $dataUri = $this->storagePathToDataUri($inspector->signature);
                if ($dataUri) {
                    $firmaCell = '<img src="' . $dataUri . '" style="max-width:120px;max-height:60px;"/>';
                }
            }
            $filas[] = '<tr><td class="foto-label">Firma</td><td>' . $firmaCell . '</td></tr>';
        }
        $idx = 1;
        foreach ($usuarios as $u) {
            $nombre = trim($u->name . ' ' . ($u->last_name ?? ''));
            $filas[] = '<tr><td class="foto-label">Usuario ' . $idx . '</td><td>' . e($nombre) . '</td></tr>';
            $idx++;
        }
        if (empty($filas)) {
            return null;
        }

        $tabla = '<table class="seccion-tabla fotos-table"><tbody>' . implode('', $filas) . '</tbody></table>';
        return '<div class="doc-seccion"><div class="doc-seccion-title">Inspector y usuarios</div><div class="doc-seccion-body">' . $tabla . '</div></div>';
    }

    private function injectBajaElementosRow(string $html, EquipoBaja $baja, Equipo $equipo): string
    {
        $formData = $this->buildBajaFormData($baja, $equipo);
        $cells = [
            $formData['CODIGO_INTERNO'] ?? $formData['CODIGO_DB'] ?? $equipo->codigo ?? '',
            $formData['DESCRIPCION'] ?? $formData['NOMBRE'] ?? $equipo->nombre ?? '',
            $formData['MARCA'] ?? '',
            $formData['SERIAL'] ?? $equipo->serial ?? '',
            $formData['FECHA_FABRICACION'] ?? $formData['FECHA_ADQUISICION'] ?? '',
            $formData['CLASIFICACION'] ?? 'Inservible',
            $formData['PROCEDENCIA'] ?? $formData['ORIGEN'] ?? $formData['EMPRESA'] ?? '',
            $formData['DESTINO_FINAL'] ?? 'Baja definitiva',
        ];

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//table') as $table) {
            $rows = $table->getElementsByTagName('tr');
            $headerIdx = -1;
            for ($r = 0; $r < $rows->length; $r++) {
                $tr = $rows->item($r);
                $text = strtolower($dom->saveHTML($tr));
                if (str_contains($text, 'codigo') && (str_contains($text, 'descripcion') || str_contains($text, 'elemento'))) {
                    $headerIdx = $r;
                    break;
                }
            }
            if ($headerIdx >= 0 && $headerIdx + 1 < $rows->length) {
                $dataTr = $rows->item($headerIdx + 1);
                $tds = $dataTr->getElementsByTagName('td');
                for ($c = 0; $c < min($tds->length, count($cells)); $c++) {
                    $td = $tds->item($c);
                    while ($td->firstChild) {
                        $td->removeChild($td->firstChild);
                    }
                    $td->appendChild($dom->createTextNode($cells[$c]));
                }
            } elseif ($headerIdx >= 0) {
                $headerTr = $rows->item($headerIdx);
                $newTr = $dom->createElement('tr');
                foreach ($cells as $val) {
                    $td = $dom->createElement('td');
                    $td->appendChild($dom->createTextNode((string) $val));
                    $td->setAttribute('style', 'border:1px solid #ccc;padding:4px 6px;');
                    $newTr->appendChild($td);
                }
                $headerTr->parentNode->insertBefore($newTr, $headerTr->nextSibling);
            }
            break;
        }

        $body = $dom->getElementsByTagName('div')->item(0);
        return $body ? $dom->saveHTML($body) : $html;
    }

    private function buildAutoFields(Equipo $equipo): array
    {
        $equipo->loadMissing(['tipoEquipo', 'claseEquipo', 'empresa', 'sede', 'bodega', 'fabricante']);
        return [
            'CODIGO' => (string) $equipo->codigo,
            'NOMBRE' => (string) $equipo->nombre,
            'SERIAL' => (string) $equipo->serial,
            'TIPO_EQUIPO' => (string) ($equipo->tipoEquipo?->nombre ?? ''),
            'CLASE_EQUIPO' => (string) ($equipo->claseEquipo?->nombre ?? ''),
            'EMPRESA' => (string) ($equipo->empresa?->nombre ?? ''),
            'SEDE' => (string) ($equipo->sede?->nombre ?? ''),
            'BODEGA' => (string) ($equipo->bodega?->nombre ?? ''),
            'FABRICANTE' => (string) ($equipo->fabricante?->name ?? ''),
            'FECHA_HOY' => now()->format('Y-m-d'),
        ];
    }

    public static function tipoOrigen(Equipo $equipo): string
    {
        $codigo = (string) ($equipo->codigo ?? '');
        if (str_starts_with($codigo, 'DB-')) {
            return 'De baja';
        }
        if (str_starts_with($codigo, 'AUD-')) {
            return 'Auditoría';
        }
        if (str_starts_with($codigo, 'MD-')) {
            return 'Material didáctico';
        }
        if (str_starts_with($codigo, 'IN-') || $codigo === '') {
            return 'Inventario';
        }
        return 'Otro';
    }
}