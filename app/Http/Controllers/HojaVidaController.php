<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\EquipoImagen;
use App\Models\HojaVidaDocumento;
use App\Models\HojaVidaPlantilla;
use App\Models\ClaseEquipo;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Services\HojaVidaAutoFields;
use App\Support\HtmlSanitizer;
use App\Support\SensitiveDocumentStorage;
use App\Support\TenantGuard;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Html as SpreadsheetHtmlReader;
use PhpOffice\PhpSpreadsheet\Writer\Html as SpreadsheetHtmlWriter;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf as SpreadsheetPdfDompdf;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class HojaVidaController extends Controller
{
    private const HV_PDF_MIN_SIZE = 5000;
    public function index(Request $request)
    {
        $this->assertCanViewModule('hoja_vida');

        return redirect()->route('formatos.index');
    }

    public function clase(Request $request, ClaseEquipo $clase)
    {
        $this->assertCanViewModule('hoja_vida');

        return redirect()->route('formatos.clase', $clase);
    }

    public function form(Request $request, ClaseEquipo $clase, Equipo $equipo)
    {
        $this->assertCanViewModule('hoja_vida');
        TenantGuard::assertClaseEquipo($clase);
        TenantGuard::assertEquipo($equipo);
        abort_unless($equipo->activo, 404);
        abort_unless((int) $equipo->clase_equipo_id === (int) $clase->id, 404);

        return redirect()->route('formatos.show', [$clase, $equipo]);
    }

    public function store(Request $request, ClaseEquipo $clase, Equipo $equipo)
    {
        $this->assertCanEditModule('hoja_vida');
        TenantGuard::assertClaseEquipo($clase);
        TenantGuard::assertEquipo($equipo);
        abort_unless($equipo->activo, 404);
        abort_unless((int) $equipo->clase_equipo_id === (int) $clase->id, 404);

        $plantilla = HojaVidaPlantilla::query()->where('clase_equipo_id', $clase->id)->first();
        if (!$plantilla || !Storage::disk('public')->exists($plantilla->plantilla_excel_path)) {
            return redirect()->route('hoja-vida.clase', $clase)->with('error', '⚠️ Esa clase de equipo no tiene plantilla válida.');
        }

        $request->validate([
            'edited_html' => ['required', 'string'],
        ]);

        return DB::transaction(function () use ($request, $clase, $equipo, $plantilla) {
            $auto = $this->buildAutoFields($equipo);

            $imagenes = EquipoImagen::query()
                ->where('equipo_id', $equipo->id)
                ->orderBy('id')
                ->limit(4)
                ->get(['id', 'path']);

            $user = null;

            $finalHtml = HtmlSanitizer::sanitizeTemplateHtml((string) $request->input('edited_html'));
            $finalHtml = $this->replaceTokens($finalHtml, $auto);
            $finalHtml = $this->blankRemainingTokens($finalHtml);

            $excelPath = $this->generateExcelFromTemplateWithImages(
                $equipo->id,
                Storage::disk('public')->path($plantilla->plantilla_excel_path),
                $auto,
                $imagenes,
                $user
            );

            HojaVidaDocumento::query()->updateOrCreate(
                ['equipo_id' => $equipo->id],
                [
                    'tipo_equipo_id' => $equipo->tipo_equipo_id,
                    'clase_equipo_id' => $equipo->clase_equipo_id,
                    'plantilla_id' => $plantilla->id,
                    'edited_html' => $finalHtml,
                    'form_data' => $auto,
                    'selected_equipo_imagen_ids' => null,
                    'signature_user_id' => null,
                    'excel_path' => $excelPath,
                    'pdf_path' => null,
                    'creado_por' => auth()->id(),
                    'actualizado_por' => auth()->id(),
                ]
            );

            return redirect()->route('hoja-vida.clase', $clase)->with('success', '✅ Hoja de vida guardada.');
        });
    }

    private function generateExcelFromTemplateWithImages(int $equipoId, string $templateAbsolutePath, array $data, $imagenes, ?User $user): string
    {
        $spreadsheet = IOFactory::load($templateAbsolutePath);

        $tokens = [];
        foreach ($data as $key => $value) {
            $token = '{{' . strtoupper((string) $key) . '}}';
            $tokens[$token] = (string) $value;
        }

        $imageTokens = [];
        for ($i = 1; $i <= 6; $i++) {
            $token = '{{IMAGEN_' . $i . '}}';
            $img = isset($imagenes[$i - 1]) ? $imagenes[$i - 1] : null;
            if ($img && $img->path && Storage::disk('public')->exists($img->path)) {
                $imageTokens[$token] = Storage::disk('public')->path($img->path);
            } else {
                $imageTokens[$token] = null;
            }
        }

        $imageTokens['{{FOTO_USUARIO}}'] = ($user && $user->photo && Storage::disk('public')->exists($user->photo))
            ? Storage::disk('public')->path($user->photo)
            : null;
        $imageTokens['{{FIRMA_USUARIO}}'] = ($user && $user->signature && Storage::disk('public')->exists($user->signature))
            ? Storage::disk('public')->path($user->signature)
            : null;

        $drawingSpecs = [
            '{{FOTO_USUARIO}}' => ['height' => 90, 'offsetX' => 2, 'offsetY' => 2],
            '{{FIRMA_USUARIO}}' => ['height' => 70, 'offsetX' => 2, 'offsetY' => 2],
        ];
        for ($i = 1; $i <= 6; $i++) {
            $drawingSpecs['{{IMAGEN_' . $i . '}}'] = ['height' => 150, 'offsetX' => 2, 'offsetY' => 2];
        }

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $coords = $sheet->getCellCollection();
            foreach ($coords as $coord) {
                $cell = $sheet->getCell($coord);
                $val = $cell->getValue();
                if (!is_string($val) || $val === '') {
                    continue;
                }

                foreach ($imageTokens as $token => $path) {
                    if (str_contains($val, $token)) {
                        $newVal = str_replace($token, '', $val);
                        if (trim($newVal) === '') {
                            $newVal = '';
                        }
                        $cell->setValueExplicit($newVal, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

                        if ($path) {
                            $spec = $drawingSpecs[$token] ?? ['height' => 120, 'offsetX' => 2, 'offsetY' => 2];
                            $drawing = new Drawing();
                            $drawing->setPath($path);
                            $drawing->setCoordinates($coord);
                            $drawing->setOffsetX((int) ($spec['offsetX'] ?? 2));
                            $drawing->setOffsetY((int) ($spec['offsetY'] ?? 2));
                            if (isset($spec['height'])) {
                                $drawing->setHeight((int) $spec['height']);
                            }
                            $drawing->setWorksheet($sheet);
                        }
                    }
                }

                $newVal = $val;
                foreach ($tokens as $token => $rep) {
                    if (str_contains($newVal, $token)) {
                        $newVal = str_replace($token, $rep, $newVal);
                    }
                }

                if ($newVal !== $val) {
                    $cell->setValueExplicit($newVal, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
            }
        }

        $filename = 'hoja_vida/excel/' . $equipoId . '-' . now()->format('YmdHis') . '.xlsx';
        $absolutePath = Storage::disk('public')->path($filename);
        Storage::disk('public')->makeDirectory('hoja_vida/excel');

        $writer = new Xlsx($spreadsheet);
        $writer->save($absolutePath);

        return $filename;
    }

    public function pdf(ClaseEquipo $clase, Equipo $equipo)
    {
        $this->assertCanViewModule('hoja_vida');
        TenantGuard::assertClaseEquipo($clase);
        TenantGuard::assertEquipo($equipo);
        abort_unless((int) $equipo->clase_equipo_id === (int) $clase->id, 404);

        if (function_exists('session_write_close')) {
            @session_write_close();
        }

        @ini_set('max_execution_time', '60');
        @set_time_limit(60);
        @ini_set('memory_limit', '512M');

        $doc = HojaVidaDocumento::query()->where('equipo_id', $equipo->id)->first();
        if (!$doc) {
            return redirect()->route('hoja-vida.form', [$clase, $equipo])->with('error', '⚠️ Primero debes guardar la hoja de vida.');
        }

        $force = (string) request()->query('force', '0') === '1';
        $download = (string) request()->query('download', '0') === '1';
        $fileHeaders = [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];
        if ($download) {
            $fileHeaders['Content-Disposition'] = 'attachment; filename="hoja_vida_' . ($equipo->codigo ?: $equipo->id) . '.pdf"';
        }
        if (!$force && $doc->pdf_path && SensitiveDocumentStorage::exists($doc->pdf_path)) {
            $size = SensitiveDocumentStorage::size($doc->pdf_path);
            if ($size >= self::HV_PDF_MIN_SIZE) {
                return SensitiveDocumentStorage::inlineFileResponse($doc->pdf_path, $fileHeaders);
            }
        }

        if ((string) request()->query('async', '1') === '1') {
            $lockKey = 'hv_pdf_generating_' . (int) $doc->id;
            $started = Cache::add($lockKey, true, now()->addMinutes(2));

            if ($started) {
                $base = base_path();
                $cmd = 'cmd /c "cd /d \\\"' . str_replace('\"', '\"\"', $base) . '\\\" && start \\\"\\\" /B php artisan hoja-vida:pdf ' . (int) $clase->id . ' ' . (int) $equipo->id . ($force ? ' --force=1' : '') . '\"';
                @pclose(@popen($cmd, 'r'));
            }

            return redirect()->route('hoja-vida.form', [$clase, $equipo])
                ->with('success', '⏳ PDF en generación. Espera 10-20 segundos y vuelve a presionar Exportar PDF.');
        }

        if (config('app.debug') && (string) request()->query('debug') === 'excel') {
            return response()->json([
                'equipo_id' => (int) $equipo->id,
                'doc_id' => (int) $doc->id,
                'excel_path' => (string) ($doc->excel_path ?? ''),
                'excel_exists' => (bool) ($doc->excel_path ? Storage::disk('public')->exists($doc->excel_path) : false),
            ]);
        }

        if ((string) request()->query('excelpdf') === '1' && $doc->excel_path && Storage::disk('public')->exists($doc->excel_path)) {
            try {
                $spreadsheet = IOFactory::load(Storage::disk('public')->path($doc->excel_path));

                $writer = new SpreadsheetPdfDompdf($spreadsheet);
                $writer->setSheetIndex(0);
                $writer->setPreCalculateFormulas(false);

                ob_start();
                try {
                    $writer->save('php://output');
                    $out = (string) ob_get_clean();
                } finally {
                    if (ob_get_level() > 0) {
                        @ob_end_clean();
                    }
                }

                if (is_string($out) && strlen($out) >= 1200) {
                    $filename = 'hoja_vida/pdf/' . $equipo->id . '-' . now()->format('YmdHis') . '.pdf';
                    SensitiveDocumentStorage::put($filename, $out);

                    $doc->update([
                        'pdf_path' => $filename,
                        'actualizado_por' => auth()->id(),
                    ]);

                    return SensitiveDocumentStorage::inlineFileResponse($filename);
                }
            } catch (\Throwable $e) {
                // fallback to HTML-based PDF below
            }
        }

        $stats = [
            'equipo_id' => (int) $equipo->id,
            'doc_id' => (int) $doc->id,
            'used_excel_fallback_initial' => false,
            'used_excel_fallback_after_sanitize' => false,
        ];

        $html = (string) $doc->edited_html;
        $stats['raw_len'] = strlen($html);
        $stats['raw_text_len'] = strlen(trim(strip_tags($html)));

        if ($stats['raw_text_len'] === 0 && $doc->excel_path && Storage::disk('public')->exists($doc->excel_path)) {
            $html = $this->excelToHtml(Storage::disk('public')->path($doc->excel_path));
            $stats['used_excel_fallback_initial'] = true;
        }

        $imagenes = [];
        $selectedIds = array_values(array_filter(array_map('intval', (array) ($doc->selected_equipo_imagen_ids ?? []))));
        if (!empty($selectedIds)) {
            $imagenes = EquipoImagen::query()
                ->where('equipo_id', $equipo->id)
                ->whereIn('id', $selectedIds)
                ->orderBy('id')
                ->get(['id', 'path']);
        }

        $user = null;
        if ($doc->signature_user_id) {
            $user = User::query()->find($doc->signature_user_id);
        }

        $html = $this->applyPdfMediaTokens($html, $imagenes, $user);
        $stats['after_tokens_len'] = strlen($html);
        $stats['after_tokens_text_len'] = strlen(trim(strip_tags($html)));

        $html = $this->convertStorageImagesForPdf($html);
        $stats['after_convert_len'] = strlen($html);
        $stats['after_convert_text_len'] = strlen(trim(strip_tags($html)));

        if ($stats['after_convert_text_len'] === 0 && $doc->excel_path && Storage::disk('public')->exists($doc->excel_path)) {
            $fallbackHtml = $this->excelToHtml(Storage::disk('public')->path($doc->excel_path));
            $fallbackHtml = $this->applyPdfMediaTokens($fallbackHtml, $imagenes, $user);
            $fallbackHtml = $this->convertStorageImagesForPdf($fallbackHtml);
            if (strlen(trim(strip_tags($fallbackHtml))) !== 0) {
                $html = $fallbackHtml;
                $stats['used_excel_fallback_after_sanitize'] = true;
            }
        }

        $extracted = $this->extractBodyAndStylesForPdf($html);
        $body = $this->sanitizeHtmlForPdf($extracted['body']);
        $body = $this->removeEmptyTableCells($body);
        $stats['after_sanitize_len'] = strlen($body);
        $stats['after_sanitize_text_len'] = strlen(trim(strip_tags($body)));
        if (config('app.debug') && (string) request()->query('debug') === '1') {
            $wrapped = $this->wrapHtmlForPdf($body, $extracted['styles']);
            $wrapped = str_replace('<body>', '<body><pre style="font-size:12px; white-space:pre-wrap;">' . e(json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>', $wrapped);

            return response($wrapped);
        }

        $wrapped = $this->wrapHtmlForPdf($body, $extracted['styles']);

        try {
            $pdf = Pdf::setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'dpi' => 96,
            ])->loadHTML($wrapped)->setPaper('a4');

            $out = $pdf->output();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('hoja-vida.form', [$clase, $equipo])
                ->with('error', 'No se pudo generar el PDF. Intenta de nuevo.');
        }

        if (!is_string($out) || strlen($out) < 1200) {
            return redirect()->route('hoja-vida.form', [$clase, $equipo])
                ->with('error', 'El PDF se generó vacío. Revisa el formato e intenta de nuevo.');
        }

        $filename = 'hoja_vida/pdf/' . $equipo->id . '-' . now()->format('YmdHis') . '.pdf';
        SensitiveDocumentStorage::put($filename, $out);

        $doc->update([
            'pdf_path' => $filename,
            'actualizado_por' => auth()->id(),
        ]);

        $fileHeaders = [];
        if ((string) request()->query('download', '0') === '1') {
            $fileHeaders['Content-Disposition'] = 'attachment; filename="hoja_vida_' . ($equipo->codigo ?: $equipo->id) . '.pdf"';
        }

        return SensitiveDocumentStorage::inlineFileResponse($filename, $fileHeaders);
    }

    public function html(ClaseEquipo $clase, Equipo $equipo)
    {
        $this->assertCanViewModule('hoja_vida');
        TenantGuard::assertClaseEquipo($clase);
        TenantGuard::assertEquipo($equipo);
        abort_unless((int) $equipo->clase_equipo_id === (int) $clase->id, 404);

        $doc = HojaVidaDocumento::query()->where('equipo_id', $equipo->id)->first();
        if (!$doc) {
            return redirect()->route('hoja-vida.form', [$clase, $equipo])->with('error', '⚠️ Primero debes guardar la hoja de vida.');
        }

        $html = (string) $doc->edited_html;
        if (trim(strip_tags($html)) === '' && $doc->excel_path && Storage::disk('public')->exists($doc->excel_path)) {
            $html = $this->excelToHtml(Storage::disk('public')->path($doc->excel_path));
        }

        $imagenes = [];
        $selectedIds = array_values(array_filter(array_map('intval', (array) ($doc->selected_equipo_imagen_ids ?? []))));
        if (!empty($selectedIds)) {
            $imagenes = EquipoImagen::query()
                ->where('equipo_id', $equipo->id)
                ->whereIn('id', $selectedIds)
                ->orderBy('id')
                ->get(['id', 'path']);
        }

        $user = null;
        if ($doc->signature_user_id) {
            $user = User::query()->find($doc->signature_user_id);
        }

        $html = $this->applyPdfMediaTokens($html, $imagenes, $user);
        $html = $this->convertStorageImagesForPdf($html);

        $extracted = $this->extractBodyAndStylesForPdf($html);
        $body = $this->sanitizeHtmlForPdf($extracted['body']);
        $body = $this->removeEmptyTableCells($body);

        $wrapped = $this->wrapHtmlForPdf($body, $extracted['styles']);

        return response($wrapped, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
    }

    public function pdfPreview(Request $request, ClaseEquipo $clase, Equipo $equipo)
    {
        $this->assertCanViewModule('hoja_vida');
        TenantGuard::assertClaseEquipo($clase);
        TenantGuard::assertEquipo($equipo);
        abort_unless((int) $equipo->clase_equipo_id === (int) $clase->id, 404);
        // La vista previa ahora se muestra en un modal; redirigir al formulario.
        return redirect()->route('hoja-vida.form', [$clase, $equipo])
            ->with('info', 'Use el botón «Exportar PDF» para ver la vista previa y descargar.');
    }

    public function pdfStatus(Request $request, ClaseEquipo $clase, Equipo $equipo)
    {
        $this->assertCanViewModule('hoja_vida');
        TenantGuard::assertClaseEquipo($clase);
        TenantGuard::assertEquipo($equipo);
        abort_unless((int) $equipo->clase_equipo_id === (int) $clase->id, 404);

        $doc = HojaVidaDocumento::query()->where('equipo_id', $equipo->id)->first();
        if (!$doc) {
            return response()->json([
                'ready' => false,
                'generating' => false,
                'error' => 'Primero debes guardar la hoja de vida.',
            ]);
        }

        $force = (string) $request->query('force', '0') === '1';

        if (!$force && $doc->pdf_path && SensitiveDocumentStorage::exists($doc->pdf_path)) {
            $size = SensitiveDocumentStorage::size($doc->pdf_path);
            if ($size >= self::HV_PDF_MIN_SIZE) {
                return response()->json([
                    'ready' => true,
                    'generating' => false,
                    'size' => $size,
                ]);
            }
        }

        $lockKey = 'hv_pdf_generating_' . (int) $doc->id;
        $lockVal = Cache::get($lockKey);
        $generating = $lockVal !== null;

        if ($generating && is_numeric($lockVal)) {
            $age = time() - (int) $lockVal;
            if ($age > 180) {
                Cache::forget($lockKey);
                $generating = false;
            }
        }

        if (!$generating) {
            $started = Cache::add($lockKey, time(), now()->addMinutes(5));
            if ($started) {
                $base = base_path();
                $cmd = 'cmd /c "cd /d \\\"' . str_replace('"', '""', $base) . '\\\" && start \\\"\\\" /B php artisan hoja-vida:pdf ' . (int) $clase->id . ' ' . (int) $equipo->id . ($force ? ' --force=1' : '') . '"';
                @pclose(@popen($cmd, 'r'));
                $generating = true;
            } else {
                return response()->json([
                    'ready' => false,
                    'generating' => false,
                    'error' => 'No se pudo iniciar la generación de PDF.',
                ]);
            }
        }

        return response()->json([
            'ready' => false,
            'generating' => (bool) $generating,
            'size' => $doc->pdf_path && SensitiveDocumentStorage::exists($doc->pdf_path)
                ? SensitiveDocumentStorage::size($doc->pdf_path)
                : 0,
        ]);
    }

    public function pdfFile(Request $request, ClaseEquipo $clase, Equipo $equipo)
    {
        $this->assertCanViewModule('hoja_vida');
        TenantGuard::assertClaseEquipo($clase);
        TenantGuard::assertEquipo($equipo);
        abort_unless((int) $equipo->clase_equipo_id === (int) $clase->id, 404);

        $doc = HojaVidaDocumento::query()->where('equipo_id', $equipo->id)->first();
        if (!$doc || !$doc->pdf_path || !SensitiveDocumentStorage::exists($doc->pdf_path)) {
            abort(404);
        }

        abort_unless(SensitiveDocumentStorage::size($doc->pdf_path) >= self::HV_PDF_MIN_SIZE, 404);

        $headers = [];
        if ((string) $request->query('download', '0') === '1') {
            $headers['Content-Disposition'] = 'attachment; filename="hoja_vida_' . (int) $equipo->id . '.pdf"';
        }

        return SensitiveDocumentStorage::inlineFileResponse($doc->pdf_path, $headers);
    }

    private function sanitizeHtmlForPdf(string $html): string
    {
        $domCleaned = null;
        if (class_exists(\DOMDocument::class)) {
            $prev = libxml_use_internal_errors(true);
            try {
                $dom = new \DOMDocument();
                $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

                $xpath = new \DOMXPath($dom);

                foreach ($xpath->query('/processing-instruction()') as $node) {
                    $node->parentNode?->removeChild($node);
                }

                foreach ($xpath->query('//script|//meta|//style') as $node) {
                    $node->parentNode?->removeChild($node);
                }

                foreach ($xpath->query('//*[@style]') as $node) {
                    $style = (string) $node->getAttribute('style');
                    $allowedProps = [
                        'background', 'background-color',
                        'color',
                        'font', 'font-family', 'font-size', 'font-weight', 'font-style',
                        'text-align', 'vertical-align',
                        'border', 'border-top', 'border-right', 'border-bottom', 'border-left',
                        'border-color', 'border-style', 'border-width',
                        'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
                        'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
                        'width', 'height', 'max-width', 'max-height', 'min-width', 'min-height',
                        'display', 'object-fit',
                        'white-space',
                    ];

                    $kept = [];
                    foreach (preg_split('/;\s*/', $style) as $decl) {
                        if (trim($decl) === '' || !str_contains($decl, ':')) {
                            continue;
                        }
                        [$prop, $val] = array_map('trim', explode(':', $decl, 2));
                        $propLower = strtolower($prop);
                        if (in_array($propLower, $allowedProps, true)) {
                            $kept[] = $propLower . ':' . $val;
                        }
                    }

                    if (!empty($kept)) {
                        $node->setAttribute('style', implode(';', $kept));
                    } else {
                        $node->removeAttribute('style');
                    }
                }

                // Conservamos class e id para respetar los estilos del Excel original

                foreach ($xpath->query('//*[@width]') as $node) {
                    if ($node->nodeName !== 'img') {
                        $node->removeAttribute('width');
                    }
                }

                foreach ($xpath->query('//*[@height]') as $node) {
                    if ($node->nodeName !== 'img') {
                        $node->removeAttribute('height');
                    }
                }

                $allowed = [
                    'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th',
                    'p', 'br', 'div', 'span',
                    'img',
                    'b', 'strong', 'i', 'em', 'u',
                    'ul', 'ol', 'li',
                ];

                $nodes = $xpath->query('//*');
                if ($nodes) {
                    foreach (iterator_to_array($nodes) as $node) {
                        $tag = strtolower((string) $node->nodeName);
                        if ($tag === 'html' || $tag === 'body') {
                            continue;
                        }

                        if (!in_array($tag, $allowed, true)) {
                            if ($node->parentNode) {
                                while ($node->firstChild) {
                                    $node->parentNode->insertBefore($node->firstChild, $node);
                                }
                                $node->parentNode->removeChild($node);
                            }
                        }
                    }
                }

                foreach ($xpath->query('//*[@contenteditable]') as $node) {
                    $node->removeAttribute('contenteditable');
                }

                foreach ($xpath->query('//button[contains(concat(" ", normalize-space(@class), " "), " hv-img-remove ")]') as $node) {
                    $node->parentNode?->removeChild($node);
                }

                foreach ($xpath->query('//button[normalize-space(text())="×"]') as $node) {
                    $node->parentNode?->removeChild($node);
                }

                $bodyNode = $xpath->query('//body')->item(0);
                if ($bodyNode) {
                    $out = '';
                    foreach (iterator_to_array($bodyNode->childNodes) as $child) {
                        $out .= $dom->saveHTML($child);
                    }
                    $domCleaned = $out;
                } else {
                    $domCleaned = $dom->saveHTML();
                }
            } catch (\Throwable $e) {
                $domCleaned = null;
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($prev);
            }
        }

        if (is_string($domCleaned) && $domCleaned !== '') {
            $domCleaned = preg_replace('/\b(rowspan|colspan)="\s*(\d+)\s*"/i', '$1="$2"', $domCleaned) ?? $domCleaned;
        }

        $cleaned = is_string($domCleaned) ? $domCleaned : $html;

        if (trim(strip_tags($cleaned)) !== '') {
            return $cleaned;
        }

        if (preg_match('/<body\b[^>]*>(.*?)<\/body>/is', $html, $m)) {
            $candidate = (string) $m[1];
            if (trim(strip_tags($candidate)) !== '') {
                $html = $candidate;
            }
        }

        $html = (string) preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        $html = (string) preg_replace('/<meta\b[^>]*>/i', '', $html);
        $html = (string) preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
        $html = (string) preg_replace('/<!DOCTYPE\b[^>]*>/i', '', $html);
        $html = (string) preg_replace('/<\/?html\b[^>]*>/i', '', $html);
        $html = (string) preg_replace('/<\/?head\b[^>]*>/i', '', $html);
        $html = (string) preg_replace('/<\/?body\b[^>]*>/i', '', $html);
        $html = (string) preg_replace('/\scontenteditable\s*=\s*(["\"]).*?\1/i', '', $html);
        $html = (string) preg_replace('/<button\b[^>]*class\s*=\s*(["\"])hv-img-remove\1[^>]*>.*?<\/button>/is', '', $html);
        $html = (string) preg_replace('/<button\b[^>]*>\s*×\s*<\/button>/is', '', $html);

        if (str_contains($html, '<img') && (str_contains($html, '/images/') || str_contains($html, 'images/'))) {
            $html = preg_replace_callback('/(<img\b[^>]*\ssrc\s*=\s*["\"])\s*([^"\\\s>]+)\s*(["\\\][^>]*>)/i', function ($m) use ($appUrl) {
                $prefix = $m[1];
                $src = $m[2];
                $suffix = $m[3];

                if (str_starts_with($src, 'file:///')) {
                    return $m[0];
                }

                $clean = $src;
                if ($appUrl !== '' && str_starts_with($clean, $appUrl . '/')) {
                    $clean = substr($clean, strlen($appUrl) + 1);
                }
                $clean = ltrim($clean, '/');

                if (!str_starts_with($clean, 'images/')) {
                    return $m[0];
                }

                $abs = public_path($clean);
                if (!is_string($abs) || $abs === '' || !file_exists($abs)) {
                    return $m[0];
                }

                $abs = str_replace('\\', '/', $abs);
                return $prefix . 'file:///' . $abs . $suffix;
            }, $html) ?? $html;
        }

        return $html;
    }

    public function download(ClaseEquipo $clase, Equipo $equipo)
    {
        $this->assertCanViewModule('hoja_vida');
        TenantGuard::assertClaseEquipo($clase);
        TenantGuard::assertEquipo($equipo);
        abort_unless((int) $equipo->clase_equipo_id === (int) $clase->id, 404);

        $doc = HojaVidaDocumento::query()->where('equipo_id', $equipo->id)->first();
        if (!$doc || !$doc->excel_path || !Storage::disk('public')->exists($doc->excel_path)) {
            return redirect()->route('hoja-vida.form', [$clase, $equipo])->with('error', '⚠️ Primero debes guardar la hoja de vida.');
        }

        $filename = 'hoja-vida-' . ($equipo->codigo ?: $equipo->id) . '.xlsx';

        return Storage::disk('public')->download($doc->excel_path, $filename);
    }

    private function excelToHtml(string $absolutePath): string
    {
        $spreadsheet = IOFactory::load($absolutePath);
        $writer = new SpreadsheetHtmlWriter($spreadsheet);
        $writer->setSheetIndex(0);
        $writer->setPreCalculateFormulas(false);

        ob_start();
        $writer->save('php://output');
        $html = (string) ob_get_clean();

        if (!class_exists(\DOMDocument::class)) {
            return $html;
        }

        $prev = libxml_use_internal_errors(true);
        try {
            $dom = new \DOMDocument();
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $xpath = new \DOMXPath($dom);

            $table = $xpath->query('//table')->item(0);
            if (!$table instanceof \DOMElement) {
                return $html;
            }

            $rows = iterator_to_array($table->getElementsByTagName('tr'));

            $maxUsedCol = -1;
            foreach ($rows as $tr) {
                if (!$tr instanceof \DOMElement) {
                    continue;
                }
                $colIndex = 0;
                foreach (iterator_to_array($tr->childNodes) as $cell) {
                    if ($cell instanceof \DOMElement && in_array(strtolower($cell->tagName), ['td', 'th'], true)) {
                        if ($this->cellHasContent($cell)) {
                            if ($colIndex > $maxUsedCol) {
                                $maxUsedCol = $colIndex;
                            }
                        }
                        $colIndex++;
                    }
                }
            }

            if ($maxUsedCol < 0) {
                return $html;
            }

            foreach ($rows as $tr) {
                if (!$tr instanceof \DOMElement) {
                    continue;
                }
                $colIndex = 0;
                foreach (iterator_to_array($tr->childNodes) as $cell) {
                    if ($cell instanceof \DOMElement && in_array(strtolower($cell->tagName), ['td', 'th'], true)) {
                        if ($colIndex > $maxUsedCol && $cell->parentNode) {
                            $cell->parentNode->removeChild($cell);
                        }
                        $colIndex++;
                    }
                }
            }

            $lastNonEmptyRowIndex = -1;
            foreach ($rows as $idx => $tr) {
                if (!$tr instanceof \DOMElement) {
                    continue;
                }
                $rowHasContent = false;
                foreach ($tr->getElementsByTagName('td') as $cell) {
                    if ($cell instanceof \DOMElement && $this->cellHasContent($cell)) {
                        $rowHasContent = true;
                        break;
                    }
                }
                if (!$rowHasContent) {
                    foreach ($tr->getElementsByTagName('th') as $cell) {
                        if ($cell instanceof \DOMElement && $this->cellHasContent($cell)) {
                            $rowHasContent = true;
                            break;
                        }
                    }
                }
                if ($rowHasContent) {
                    $lastNonEmptyRowIndex = $idx;
                }
            }

            if ($lastNonEmptyRowIndex >= 0) {
                foreach (array_slice($rows, $lastNonEmptyRowIndex + 1) as $tr) {
                    if ($tr instanceof \DOMElement && $tr->parentNode) {
                        $tr->parentNode->removeChild($tr);
                    }
                }
            }

            $clean = $dom->saveHTML();
            return is_string($clean) && $clean !== '' ? $clean : $html;
        } catch (\Throwable $e) {
            return $html;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($prev);
        }
    }

    /**
     * Elimina celdas de tabla vacías (td/th sin contenido) para la vista previa.
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
        // Quitar espacios no separables de Excel (&nbsp;) para que no cuenten como contenido
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

    private function buildAutoFields(Equipo $equipo): array
    {
        return HojaVidaAutoFields::forEquipo($equipo);
    }

    private function cleanTemplateArtifacts(string $html): string
    {
        $html = str_replace(['#REF!', '#VALUE!', '#NAME?', '#DIV/0!'], '', $html);
        $html = (string) preg_replace('/\s*=\s*[A-Z0-9_\.]+\([^<]{0,500}\)/i', '', $html);
        $html = (string) preg_replace('/\s*=\s*[A-Z0-9_\.]+\([^\r\n<]{0,500}\)/i', '', $html);

        return $html;
    }

    private function generateExcel(int $equipoId, string $html): string
    {
        $html = (string) preg_replace('/<img\b[^>]*>/i', '', $html);

        $html = (string) preg_replace('/<meta\b[^>]*>/i', '', $html);
        $html = (string) preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);

        $reader = new SpreadsheetHtmlReader();
        $spreadsheet = $reader->loadFromString($this->wrapHtmlForSpreadsheet($html));

        foreach ($spreadsheet->getAllSheets() as $idx => $sheet) {
            $title = (string) $sheet->getTitle();
            $title = trim($title);
            $title = str_replace(['\\', '/', '?', '*', ':', '[', ']'], ' ', $title);
            $title = (string) preg_replace('/\s+/', ' ', $title);
            $title = mb_substr($title, 0, 31);
            if ($title === '') {
                $title = 'Hoja' . ($idx + 1);
            }

            $sheet->setTitle($title);
        }

        $writer = new Xlsx($spreadsheet);

        $filename = 'hoja_vida/excel/' . $equipoId . '-' . now()->format('YmdHis') . '.xlsx';
        $absolutePath = Storage::disk('public')->path($filename);

        Storage::disk('public')->makeDirectory('hoja_vida/excel');
        $writer->save($absolutePath);

        return $filename;
    }

    private function generateExcelFromTemplateOverlayHtml(int $equipoId, string $templateAbsolutePath, string $html): string
    {
        $template = IOFactory::load($templateAbsolutePath);

        $html = (string) preg_replace('/<img\b[^>]*>/i', '', $html);
        $html = (string) preg_replace('/<meta\b[^>]*>/i', '', $html);
        $html = (string) preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);

        $reader = new SpreadsheetHtmlReader();
        $overlay = $reader->loadFromString($this->wrapHtmlForSpreadsheet($html));

        foreach ($template->getAllSheets() as $idx => $sheet) {
            $overlaySheet = $overlay->getSheetCount() > $idx ? $overlay->getSheet($idx) : null;
            if (!$overlaySheet) {
                continue;
            }

            $highestRow = (int) $overlaySheet->getHighestRow();
            $highestCol = (string) $overlaySheet->getHighestColumn();
            $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

            for ($row = 1; $row <= $highestRow; $row++) {
                for ($col = 1; $col <= $highestColIndex; $col++) {
                    $cell = $overlaySheet->getCellByColumnAndRow($col, $row);
                    $val = $cell ? $cell->getValue() : null;
                    if ($val === null || $val === '') {
                        continue;
                    }

                    $sheet->setCellValueByColumnAndRow($col, $row, $val);
                }
            }
        }

        $filename = 'hoja_vida/excel/' . $equipoId . '-' . now()->format('YmdHis') . '.xlsx';
        $absolutePath = Storage::disk('public')->path($filename);

        Storage::disk('public')->makeDirectory('hoja_vida/excel');
        $writer = new Xlsx($template);
        $writer->save($absolutePath);

        return $filename;
    }

    private function generateExcelFromTemplate(int $equipoId, string $templateAbsolutePath, array $data): string
    {
        $spreadsheet = IOFactory::load($templateAbsolutePath);

        $tokens = [];
        foreach ($data as $key => $value) {
            $token = '{{' . strtoupper((string) $key) . '}}';
            $tokens[$token] = (string) $value;
        }

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $cellIterator = $sheet->getCellCollection();
            foreach ($cellIterator as $coord) {
                $cell = $sheet->getCell($coord);
                $val = $cell->getValue();
                if (!is_string($val) || $val === '') {
                    continue;
                }

                $newVal = $val;
                foreach ($tokens as $token => $rep) {
                    if (str_contains($newVal, $token)) {
                        $newVal = str_replace($token, $rep, $newVal);
                    }
                }

                if ($newVal !== $val) {
                    $cell->setValueExplicit($newVal, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
            }
        }

        $filename = 'hoja_vida/excel/' . $equipoId . '-' . now()->format('YmdHis') . '.xlsx';
        $absolutePath = Storage::disk('public')->path($filename);

        Storage::disk('public')->makeDirectory('hoja_vida/excel');
        $writer = new Xlsx($spreadsheet);
        $writer->save($absolutePath);

        return $filename;
    }

    private function wrapHtmlForSpreadsheet(string $body): string
    {
        return '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $body . '</body></html>';
    }

    /**
     * Extrae solo el contenido del body (tabla) y los estilos para el head.
     * Quita todo bloque <style> del body para que en el PDF solo se vea la tabla, no código.
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

        // Quitar del body cualquier <style> para que no se muestre como texto en el PDF
        $body = (string) preg_replace('/<style[^>]*>.*?<\/style>/is', '', $body);
        $body = trim($body);

        return ['body' => $body, 'styles' => $styles];
    }

    private function wrapHtmlForPdf(string $body, string $extraStyles = ''): string
    {
        $css = 'html,body{font-family:DejaVu Sans,sans-serif;font-size:10px;margin:0;padding:12px;color:#333;}'
            . ' table{width:100%;border-collapse:collapse;margin-bottom:8px;table-layout:fixed;}'
            . ' td,th{border:1px solid #666;padding:4px 6px;vertical-align:top;word-wrap:break-word;}'
            . ' th{background:#e8e8e8;font-weight:bold;}'
            . ' img{max-width:100%;height:auto;display:inline-block;vertical-align:middle;}'
            . ' .inspeccion-firma-wrap,.hv-img-wrap{display:inline-block;}';
        $headStyles = '<style>' . $css . '</style>';
        if ($extraStyles !== '') {
            $headStyles .= '<style>' . $extraStyles . '</style>';
        }
        return '<!DOCTYPE html><html><head><meta charset="utf-8">' . $headStyles . '</head><body>' . $body . '</body></html>';
    }

    private function applyPdfMediaTokens(string $html, $imagenes, ?User $user): string
    {
        for ($i = 1; $i <= 6; $i++) {
            $token = '{{IMAGEN_' . $i . '}}';
            $img = isset($imagenes[$i - 1]) ? $imagenes[$i - 1] : null;

            if ($img && $img->path) {
                $dataUri = $this->storagePathToDataUri($img->path);
                if ($dataUri !== null) {
                    $html = str_replace($token, '<img src="' . $dataUri . '" style="max-width: 240px; max-height: 240px;">', $html);
                } else {
                    $html = str_replace($token, '', $html);
                }
            } else {
                $html = str_replace($token, '', $html);
            }
        }

        $firmaToken = '{{FIRMA_USUARIO}}';
        $fotoToken = '{{FOTO_USUARIO}}';

        $firmaHtml = '';
        $fotoHtml = '';

        if ($user) {
            if ($user->signature) {
                $dataUri = $this->storagePathToDataUri($user->signature);
                if ($dataUri !== null) {
                    $firmaHtml = '<img src="' . $dataUri . '" style="max-width: 220px; max-height: 120px;">';
                }
            }

            if ($user->photo) {
                $dataUri = $this->storagePathToDataUri($user->photo);
                if ($dataUri !== null) {
                    $fotoHtml = '<img src="' . $dataUri . '" style="max-width: 120px; max-height: 120px; border-radius: 8px;">';
                }
            }
        }

        $html = str_replace($firmaToken, $firmaHtml, $html);
        $html = str_replace($fotoToken, $fotoHtml, $html);

        return $html;
    }

    private function storagePathToDataUri(string $path): ?string
    {
        return \App\Support\SafeStoragePath::toDataUri($path);
    }

    private function convertStorageImagesForPdf(string $html): string
    {
        $appUrl = rtrim((string) config('app.url'), '/');

        if (!str_contains($html, '<img') && !str_contains($html, 'storage/')) {
            return $html;
        }

        if (class_exists(\DOMDocument::class)) {
            $prev = libxml_use_internal_errors(true);
            try {
                $dom = new \DOMDocument();
                $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                $xpath = new \DOMXPath($dom);

                foreach ($xpath->query('//img[@src]') as $img) {
                    $src = (string) $img->getAttribute('src');
                    if (str_starts_with($src, 'data:')) {
                        continue;
                    }

                    $storagePath = null;

                    if ($appUrl !== '' && str_starts_with($src, $appUrl . '/storage/')) {
                        $rel = substr($src, strlen($appUrl . '/storage/'));
                        $rel = preg_replace('/\?.*$/', '', $rel);
                        $storagePath = ltrim($rel, '/');
                    } elseif (str_starts_with($src, '/storage/')) {
                        $rel = preg_replace('/\?.*$/', '', substr($src, strlen('/storage/')));
                        $storagePath = ltrim($rel, '/');
                    } elseif (str_starts_with($src, 'storage/')) {
                        $rel = preg_replace('/\?.*$/', '', substr($src, strlen('storage/')));
                        $storagePath = ltrim($rel, '/');
                    }

                    if ($storagePath) {
                        $dataUri = $this->storagePathToDataUri($storagePath);
                        if ($dataUri !== null) {
                            $img->setAttribute('src', $dataUri);
                        }
                    }
                }

                $html = $dom->saveHTML();
            } catch (\Throwable $e) {
                // fallback below
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($prev);
            }
        }

        if (str_contains($html, 'storage/') || ($appUrl !== '' && str_contains($html, $appUrl . '/storage/'))) {
            $pattern = '/\b(?:' . preg_quote($appUrl, '/') . '\/storage\/|\/storage\/|storage\/)([^\s<\"\'\?]+\.(?:png|jpe?g|webp|gif))(?:\?[^\s"\']*)?/i';
            $replaced = preg_replace_callback(
                $pattern,
                function ($m) {
                    $rel = (string) $m[1];
                    $path = ltrim($rel, '/');
                    $dataUri = $this->storagePathToDataUri($path);
                    return $dataUri !== null ? $dataUri : $m[0];
                },
                $html
            );

            if ($replaced !== null) {
                $html = (string) $replaced;
            }
        }

        return $html;
    }
}