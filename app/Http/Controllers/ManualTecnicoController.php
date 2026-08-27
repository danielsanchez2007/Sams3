<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

class ManualTecnicoController extends Controller
{
    /**
     * Solo administrador global SAMS (usuario sin empresa asignada).
     */
    private function assertGlobalAdministrator(): void
    {
        $u = auth()->user();
        abort_unless($u && $u->empresa_id === null, 403, 'Solo el administrador global puede descargar el manual técnico.');
    }

    /**
     * @return array<string, mixed>
     */
    private function manualViewData(): array
    {
        return [
            'generatedAt' => now()->toIso8601String(),
            'generatedHuman' => now()->timezone(config('app.timezone'))->format('Y-m-d H:i:s T'),
            'documentId' => 'SAMS-MT-'.now()->format('Y'),
            'documentVersion' => '1.0.0',
            'documentClassification' => 'Uso interno — Prevention World',
            'organization' => 'Prevention World · Instituto Prevention World',
            'appName' => config('app.name'),
            'appUrl' => config('app.url'),
            'laravelVersion' => app()->version(),
            'phpVersion' => PHP_VERSION,
            'composerRows' => $this->composerRequireWithInstalledVersions(),
            'composerDevRows' => $this->composerRequireDevWithInstalledVersions(),
            'npmRows' => $this->npmRootDependenciesWithInstalledVersions(),
            'phpLimits' => [
                'upload_max_filesize' => ini_get('upload_max_filesize') ?: '(no disponible)',
                'post_max_size' => ini_get('post_max_size') ?: '(no disponible)',
                'max_execution_time' => ini_get('max_execution_time') ?: '(no disponible)',
                'memory_limit' => ini_get('memory_limit') ?: '(no disponible)',
            ],
            'routeRows' => $this->routesForManual(),
            'modelFiles' => $this->phpBasenamesInDir(app_path('Models')),
            'controllerFiles' => $this->phpBasenamesInDir(app_path('Http/Controllers')),
            'middlewareFiles' => $this->phpBasenamesInDir(app_path('Http/Middleware')),
            'serviceFiles' => $this->listServicePhpFiles(),
            'consoleCommands' => $this->phpBasenamesInDir(app_path('Console/Commands')),
            'configFiles' => $this->phpBasenamesInDir(config_path()),
            'migrationCount' => count(File::glob(database_path('migrations').'/*.php')),
        ];
    }

    public function download(): Response
    {
        $this->assertGlobalAdministrator();

        $html = view('docs.manual-tecnico', $this->manualViewData())->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="SAMS-Manual-Tecnico.html"',
        ]);
    }

    public function downloadPdf(): Response
    {
        $this->assertGlobalAdministrator();

        if (function_exists('set_time_limit')) {
            @set_time_limit(180);
        }
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '512M');
        }

        $data = $this->manualViewData();
        $routeCount = count($data['routeRows'] ?? []);

        $parts = [
            $this->renderManualPdfBinary($data, 'pre_routes', 'portrait', false),
            $this->renderManualPdfBinary(
                $data,
                'routes',
                $routeCount > 0 ? 'landscape' : 'portrait',
                $routeCount > 0
            ),
            $this->renderManualPdfBinary($data, 'post_routes', 'portrait', false),
        ];

        try {
            $binary = $this->mergePdfBinaries($parts);
        } catch (\Throwable $e) {
            report($e);
            $binary = $this->renderManualPdfBinary($data, 'all', 'portrait', false);
        }

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="SAMS-Manual-Tecnico.pdf"',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function renderManualPdfBinary(array $data, string $pdfSection, string $paper, bool $pdfLandscape): string
    {
        $payload = array_merge($data, [
            'pdfSection' => $pdfSection,
            'pdfLandscape' => $pdfLandscape,
        ]);

        return Pdf::setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
            'defaultFont' => 'DejaVu Sans',
        ])
            ->loadView('docs.manual-tecnico-pdf', $payload)
            ->setPaper('a4', $paper)
            ->output();
    }

    /**
     * @param  list<string>  $binaries
     */
    private function mergePdfBinaries(array $binaries): string
    {
        if ($binaries === []) {
            return '';
        }

        if (count($binaries) === 1) {
            return $binaries[0];
        }

        $pdf = new Fpdi;

        foreach ($binaries as $raw) {
            $pageCount = $pdf->setSourceFile(StreamReader::createByString($raw));
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $tplId = $pdf->importPage($pageNo);
                $size = $pdf->getImportedPageSize($tplId);
                if ($size === false) {
                    throw new \RuntimeException('No se pudo leer el tamaño de una página importada.');
                }
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useImportedPage($tplId, 0, 0, $size['width'], $size['height']);
            }
        }

        return $pdf->Output('S');
    }

    /**
     * @return list<array{name: string, constraint: string, installed: string}>
     */
    private function composerRequireWithInstalledVersions(): array
    {
        $composerPath = base_path('composer.json');
        $lockPath = base_path('composer.lock');
        if (! File::exists($composerPath) || ! File::exists($lockPath)) {
            return [];
        }
        $composer = json_decode(File::get($composerPath), true) ?: [];
        $lock = json_decode(File::get($lockPath), true) ?: [];
        $byName = [];
        foreach ($lock['packages'] ?? [] as $pkg) {
            if (! empty($pkg['name'])) {
                $byName[(string) $pkg['name']] = (string) ($pkg['version'] ?? '');
            }
        }
        $require = $composer['require'] ?? [];
        $rows = [];
        foreach ($require as $name => $constraint) {
            $name = (string) $name;
            $rows[] = [
                'name' => $name,
                'constraint' => (string) $constraint,
                'installed' => $byName[$name] ?? '— (paquete de plataforma / no aparece en lock como paquete raíz)',
            ];
        }
        usort($rows, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $rows;
    }

    /**
     * @return list<array{name: string, constraint: string, installed: string}>
     */
    private function composerRequireDevWithInstalledVersions(): array
    {
        $composerPath = base_path('composer.json');
        $lockPath = base_path('composer.lock');
        if (! File::exists($composerPath) || ! File::exists($lockPath)) {
            return [];
        }
        $composer = json_decode(File::get($composerPath), true) ?: [];
        $lock = json_decode(File::get($lockPath), true) ?: [];
        $byName = [];
        foreach ($lock['packages'] ?? [] as $pkg) {
            if (! empty($pkg['name'])) {
                $byName[(string) $pkg['name']] = (string) ($pkg['version'] ?? '');
            }
        }
        $requireDev = $composer['require-dev'] ?? [];
        $rows = [];
        foreach ($requireDev as $name => $constraint) {
            $name = (string) $name;
            $rows[] = [
                'name' => $name,
                'constraint' => (string) $constraint,
                'installed' => $byName[$name] ?? '—',
            ];
        }
        usort($rows, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $rows;
    }

    /**
     * Rutas generadas en tiempo de compilación del manual (excluye rutas de prueba Dusk y health).
     *
     * @return list<array{methods: string, uri: string, name: string, action: string}>
     */
    private function routesForManual(): array
    {
        try {
            Artisan::call('route:list', ['--json' => true]);
            $raw = json_decode(Artisan::output(), true);
            if (! is_array($raw)) {
                return [];
            }
        } catch (\Throwable) {
            return [];
        }
        $rows = [];
        foreach ($raw as $r) {
            $uri = (string) ($r['uri'] ?? '');
            if (str_starts_with($uri, '_dusk')) {
                continue;
            }
            if ($uri === 'up') {
                continue;
            }
            $rows[] = [
                'methods' => (string) ($r['method'] ?? ''),
                'uri' => $uri,
                'name' => (string) ($r['name'] ?? '—'),
                'action' => (string) ($r['action'] ?? ''),
            ];
        }
        usort($rows, fn ($a, $b) => strcmp($a['uri'], $b['uri']));

        return array_slice($rows, 0, 320);
    }

    /**
     * @return list<string>
     */
    private function phpBasenamesInDir(string $dir): array
    {
        if (! is_dir($dir)) {
            return [];
        }
        $files = File::glob(rtrim($dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'*.php');
        $names = array_map(fn (string $f): string => basename($f), $files);
        sort($names);

        return $names;
    }

    /**
     * @return list<string>
     */
    private function listServicePhpFiles(): array
    {
        $root = app_path('Services');
        if (! is_dir($root)) {
            return [];
        }
        $files = File::allFiles($root);
        $rel = [];
        foreach ($files as $f) {
            if ($f->getExtension() !== 'php') {
                continue;
            }
            $real = $f->getRealPath();
            if ($real === false) {
                continue;
            }
            $rel[] = str_replace('\\', '/', substr($real, strlen($root) + 1));
        }
        sort($rel);

        return $rel;
    }

    /**
     * @return list<array{name: string, range: string, installed: string, kind: string}>
     */
    private function npmRootDependenciesWithInstalledVersions(): array
    {
        $lockPath = base_path('package-lock.json');
        if (! File::exists($lockPath)) {
            return [];
        }
        $lock = json_decode(File::get($lockPath), true) ?: [];
        $root = $lock['packages'][''] ?? [];
        $packages = $lock['packages'] ?? [];
        $out = [];
        foreach (['dependencies' => 'runtime', 'devDependencies' => 'dev'] as $key => $kind) {
            foreach ($root[$key] ?? [] as $name => $range) {
                $name = (string) $name;
                $pathKey = 'node_modules/'.$name;
                $installed = $packages[$pathKey]['version'] ?? (string) $range;
                $out[] = [
                    'name' => $name,
                    'range' => (string) $range,
                    'installed' => (string) $installed,
                    'kind' => $kind,
                ];
            }
        }
        usort($out, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $out;
    }
}
