<?php

namespace App\Console\Commands;

use App\Models\ClaseEquipo;
use App\Models\Equipo;
use App\Models\EquipoImagen;
use App\Models\HojaVidaDocumento;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Support\SensitiveDocumentStorage;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Html as SpreadsheetHtmlWriter;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf as SpreadsheetPdfDompdf;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class GenerateHojaVidaPdfCommand extends Command
{
    protected $signature = 'hoja-vida:pdf {clase} {equipo} {--force=0}';

    protected $description = 'Genera el PDF de Hoja de Vida en segundo plano y lo guarda en storage';

    public function handle(): int
    {
        $claseId = (int) $this->argument('clase');
        $equipoId = (int) $this->argument('equipo');
        $force = (string) $this->option('force') === '1';

        @ini_set('max_execution_time', '120');
        @set_time_limit(120);
        @ini_set('memory_limit', '768M');

        $doc = null;
        $lockKey = null;
        try {
            $clase = ClaseEquipo::query()->findOrFail($claseId);
            $equipo = Equipo::query()->findOrFail($equipoId);

            $doc = HojaVidaDocumento::query()->where('equipo_id', $equipo->id)->first();
            if (!$doc) {
                $this->error('No existe HojaVidaDocumento para el equipo.');
                return self::FAILURE;
            }

            $lockKey = 'hv_pdf_generating_' . (int) $doc->id;

            if (!$force && $doc->pdf_path && SensitiveDocumentStorage::exists($doc->pdf_path)) {
                $size = SensitiveDocumentStorage::size($doc->pdf_path);
                if ($size >= 20000) {
                    $this->info('PDF ya existe: ' . $doc->pdf_path);
                    return self::SUCCESS;
                }
            }

            if ($doc->excel_path && Storage::disk('public')->exists($doc->excel_path)) {
                try {
                    $spreadsheet = IOFactory::load(Storage::disk('public')->path($doc->excel_path));

                    $sheet = $spreadsheet->getSheet(0);
                    $dim = $sheet->calculateWorksheetDimension();
                    $parts = explode(':', $dim);
                    $end = $parts[1] ?? 'A1';
                    $endCol = preg_replace('/\d+/', '', $end);
                    $endIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($endCol);
                    $orientation = $endIndex > 9 ? PageSetup::ORIENTATION_LANDSCAPE : PageSetup::ORIENTATION_PORTRAIT;
                    $sheet->getPageSetup()->setOrientation($orientation);
                    $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
                    $sheet->getPageSetup()->setFitToWidth(1);
                    $sheet->getPageSetup()->setFitToHeight(0);
                    $sheet->getPageSetup()->setHorizontalCentered(true);

                    // Preferred: Excel -> HTML (inline CSS) -> DomPDF (keeps colors/layout closer to editor preview)
                    $html = $this->excelToHtml(Storage::disk('public')->path($doc->excel_path));

                    $imagenes = EquipoImagen::query()
                        ->where('equipo_id', $equipo->id)
                        ->orderBy('id')
                        ->limit(4)
                        ->get(['id', 'path']);

                    $html = $this->applyPdfMediaTokens($html, $imagenes, null);
                    $html = $this->convertStorageImagesForPdf($html);
                    $html = $this->sanitizeHtmlForPdf($html);
                    $html = preg_replace('/<\?xml\b[^>]*\?>/i', '', $html) ?? $html;
                    $wrapped = $this->wrapHtmlForPdf($html);

                    $pdf = Pdf::setOptions([
                        'isHtml5ParserEnabled' => true,
                        'isRemoteEnabled' => false,
                        'dpi' => 96,
                    ])->loadHTML($wrapped);

                    if ($orientation === PageSetup::ORIENTATION_LANDSCAPE) {
                        $pdf->setPaper('a4', 'landscape');
                    } else {
                        $pdf->setPaper('a4', 'portrait');
                    }

                    $out = $pdf->output();
                    if (is_string($out) && strlen($out) >= 12000) {
                        $filename = 'hoja_vida/pdf/' . $equipo->id . '-' . now()->format('YmdHis') . '.pdf';
                        SensitiveDocumentStorage::put($filename, $out);

                        $doc->update([
                            'pdf_path' => $filename,
                        ]);

                        $this->info('PDF generado (Excel->HTML): private/' . $filename);
                        return self::SUCCESS;
                    }

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

                    if (is_string($out) && strlen($out) >= 12000) {
                        $filename = 'hoja_vida/pdf/' . $equipo->id . '-' . now()->format('YmdHis') . '.pdf';
                        SensitiveDocumentStorage::put($filename, $out);

                        $doc->update([
                            'pdf_path' => $filename,
                        ]);

                        $this->info('PDF generado (Excel): private/' . $filename);
                        return self::SUCCESS;
                    }
                } catch (\Throwable $e) {
                    // fallback to HTML->PDF below
                }
            }

            $html = (string) $doc->edited_html;

            if (strlen(trim(strip_tags($html))) === 0 && $doc->excel_path && Storage::disk('public')->exists($doc->excel_path)) {
                $html = $this->excelToHtml(Storage::disk('public')->path($doc->excel_path));
            }

            $selectedIds = array_values(array_filter(array_map('intval', (array) ($doc->selected_equipo_imagen_ids ?? []))));

            $imagenes = [];
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
            $html = $this->sanitizeHtmlForPdf($html);
            $html = preg_replace('/<\?xml\b[^>]*\?>/i', '', $html) ?? $html;

            $textLen = strlen(trim(strip_tags($html)));
            $imgCount = preg_match_all('/<img\b/i', $html) ?: 0;
            if ($textLen === 0 && $imgCount === 0) {
                $debugHtml = 'hoja_vida/pdf_debug/' . $equipo->id . '-' . now()->format('YmdHis') . '.html';
                Storage::disk('public')->makeDirectory('hoja_vida/pdf_debug');
                Storage::disk('public')->put($debugHtml, $this->wrapHtmlForPdf($html));
                $this->error('HTML vacío tras sanitizar. Debug: storage/' . $debugHtml);
                return self::FAILURE;
            }
            $wrapped = $this->wrapHtmlForPdf($html);

            $pdf = Pdf::setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'dpi' => 96,
            ])->loadHTML($wrapped)->setPaper('a4');

            $out = $pdf->output();
            if (!is_string($out) || strlen($out) < 20000) {
                $debugHtml = 'hoja_vida/pdf_debug/' . $equipo->id . '-' . now()->format('YmdHis') . '.html';
                Storage::disk('public')->makeDirectory('hoja_vida/pdf_debug');
                Storage::disk('public')->put($debugHtml, $wrapped);
                $this->error('PDF vacío. Debug: storage/' . $debugHtml);
                return self::FAILURE;
            }

            $filename = 'hoja_vida/pdf/' . $equipo->id . '-' . now()->format('YmdHis') . '.pdf';
            SensitiveDocumentStorage::put($filename, $out);

            $doc->update([
                'pdf_path' => $filename,
            ]);

            $this->info('PDF generado: private/' . $filename);
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        } finally {
            if ($doc) {
                $lockKey = 'hv_pdf_generating_' . (int) $doc->id;
                Cache::forget($lockKey);
            }
        }
    }

    private function excelToHtml(string $absolutePath): string
    {
        $spreadsheet = IOFactory::load($absolutePath);
        $writer = new SpreadsheetHtmlWriter($spreadsheet);
        $writer->setUseInlineCss(true);

        ob_start();
        try {
            $writer->save('php://output');
            return (string) ob_get_clean();
        } finally {
            if (ob_get_level() > 0) {
                @ob_end_clean();
            }
        }
    }

    private function wrapHtmlForPdf(string $body): string
    {
        return '<!DOCTYPE html><html><head><meta charset="utf-8"><style>html,body{font-family:DejaVu Sans, sans-serif; font-size:10px;} table{width:100%; table-layout:fixed; border-collapse:collapse;} td,th{border:0.5px solid #999; padding:2px; vertical-align:top; word-wrap:break-word;} img{max-width:100%;}</style></head><body>' . $body . '</body></html>';
    }

    private function applyPdfMediaTokens(string $html, $imagenes, ?User $user): string
    {
        for ($i = 1; $i <= 6; $i++) {
            $token = '{{IMAGEN_' . $i . '}}';
            $img = $imagenes[$i - 1] ?? null;
            if ($img && !empty($img->path) && Storage::disk('public')->exists($img->path)) {
                $html = str_replace($token, '<img src="file:///' . str_replace('\\', '/', Storage::disk('public')->path($img->path)) . '" />', $html);
            } else {
                $html = str_replace($token, '', $html);
            }
        }

        $userPhoto = '{{FOTO_USUARIO}}';
        $userSign = '{{FIRMA_USUARIO}}';

        if ($user && $user->photo && Storage::disk('public')->exists($user->photo)) {
            $html = str_replace($userPhoto, '<img src="file:///' . str_replace('\\', '/', Storage::disk('public')->path($user->photo)) . '" />', $html);
        } else {
            $html = str_replace($userPhoto, '', $html);
        }

        if ($user && $user->signature && Storage::disk('public')->exists($user->signature)) {
            $html = str_replace($userSign, '<img src="file:///' . str_replace('\\', '/', Storage::disk('public')->path($user->signature)) . '" />', $html);
        } else {
            $html = str_replace($userSign, '', $html);
        }

        return $html;
    }

    private function convertStorageImagesForPdf(string $html): string
    {
        if (str_contains($html, 'storage/')) {
            $html = preg_replace_callback('/(<img\b[^>]*\ssrc\s*=\s*["\"])\s*([^"\\\s>]+)\s*(["\\\][^>]*>)/i', function ($m) {
                $prefix = $m[1];
                $src = $m[2];
                $suffix = $m[3];

                if (str_starts_with($src, 'file:///')) {
                    return $m[0];
                }

                $clean = preg_replace('/^https?:\/\/[^\/]+\//i', '', $src);
                $clean = ltrim((string) $clean, '/');
                if (!str_starts_with($clean, 'storage/')) {
                    return $m[0];
                }

                $rel = substr($clean, strlen('storage/'));
                if ($rel === false || $rel === '') {
                    return $m[0];
                }

                if (!Storage::disk('public')->exists($rel)) {
                    return $m[0];
                }

                $abs = Storage::disk('public')->path($rel);
                $abs = str_replace('\\', '/', $abs);
                return $prefix . 'file:///' . $abs . $suffix;
            }, $html) ?? $html;
        }

        if (str_contains($html, '<img') && (str_contains($html, '/images/') || str_contains($html, 'images/'))) {
            $html = preg_replace_callback('/(<img\b[^>]*\ssrc\s*=\s*["\"])\s*([^"\\\s>]+)\s*(["\\\][^>]*>)/i', function ($m) {
                $prefix = $m[1];
                $src = $m[2];
                $suffix = $m[3];

                if (str_starts_with($src, 'file:///')) {
                    return $m[0];
                }

                $clean = preg_replace('/^https?:\/\/[^\/]+\//i', '', $src);
                $clean = ltrim((string) $clean, '/');
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

    private function sanitizeHtmlForPdf(string $html): string
    {
        if (!class_exists(\DOMDocument::class)) {
            return $html;
        }

        $prev = libxml_use_internal_errors(true);
        try {
            $dom = new \DOMDocument();
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

            $xpath = new \DOMXPath($dom);

            foreach ($xpath->query('/processing-instruction()') as $node) {
                $node->parentNode?->removeChild($node);
            }

            foreach ($xpath->query('//script|//style|//meta') as $node) {
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
                    'width', 'height',
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

            foreach ($xpath->query('//*[@class]') as $node) {
                $node->removeAttribute('class');
            }

            foreach ($xpath->query('//*[@id]') as $node) {
                $node->removeAttribute('id');
            }

            foreach ($xpath->query('//img[@src]') as $img) {
                $src = (string) $img->getAttribute('src');
                if (str_starts_with($src, 'data:') || str_starts_with($src, 'http://') || str_starts_with($src, 'https://')) {
                    $img->parentNode?->removeChild($img);
                    continue;
                }
                if ($src !== '' && !str_starts_with($src, 'file:///')) {
                    $img->parentNode?->removeChild($img);
                    continue;
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

            $bodyNode = $xpath->query('//body')->item(0);
            if ($bodyNode) {
                $out = '';
                foreach (iterator_to_array($bodyNode->childNodes) as $child) {
                    $out .= $dom->saveHTML($child);
                }
                $out = preg_replace('/\b(rowspan|colspan)="\s*(\d+)\s*"/i', '$1="$2"', $out) ?? $out;
                return $out;
            }

            $out = (string) $dom->saveHTML();
            $out = preg_replace('/\b(rowspan|colspan)="\s*(\d+)\s*"/i', '$1="$2"', $out) ?? $out;
            return $out;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($prev);
        }
    }
}
