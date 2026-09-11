<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;

class SamsPackageCommand extends Command
{
    protected $signature = 'sams:package {--skip-build : No recompilar Vite}';

    protected $description = 'Genera un ZIP listo para subir (código, vendor, estilos y sin .env)';

    public function handle(): int
    {
        if (! $this->option('skip-build')) {
            $this->info('Compilando estilos y JavaScript...');
            $code = 0;
            passthru('npm run build', $code);
            if ($code !== 0) {
                $this->error('Falló npm run build. Revisa Node/npm en este PC.');

                return self::FAILURE;
            }
        }

        if (! is_file(base_path('vendor/autoload.php'))) {
            $this->error('Falta vendor/. Ejecuta: composer install --no-dev --optimize-autoloader');

            return self::FAILURE;
        }

        if (! is_file(public_path('build/manifest.json')) && ! is_file(public_path('css/sams.css'))) {
            $this->error('No hay assets compilados. Ejecuta npm run build.');

            return self::FAILURE;
        }

        $dist = base_path('dist');
        File::ensureDirectoryExists($dist);
        $zipPath = $dist.DIRECTORY_SEPARATOR.'sams-subir.zip';
        if (is_file($zipPath)) {
            @unlink($zipPath);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error('No se pudo crear el ZIP.');

            return self::FAILURE;
        }

        $excludeDirs = [
            '.git', '.idea', '.vscode', '.cursor', 'node_modules', 'tests', 'dist',
            'storage/logs', 'storage/framework/cache/data', 'storage/framework/sessions',
            'storage/framework/views', 'storage/pail',
        ];
        $excludeFiles = ['.env', '.env.backup', '.env.production', '.env.temp', 'public/hot', 'auth.json'];

        $root = realpath(base_path());
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            $absolute = $file->getPathname();
            $relative = str_replace('\\', '/', substr($absolute, strlen($root) + 1));

            if ($this->excluded($relative, $excludeDirs, $excludeFiles)) {
                continue;
            }

            if ($file->isDir()) {
                $zip->addEmptyDir($relative);
            } else {
                $zip->addFile($absolute, $relative);
            }
        }

        $zip->addFromString('COMO-SUBIR.txt', $this->instructions());
        $zip->close();

        $this->info('Paquete listo: '.$zipPath);

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $excludeDirs
     * @param  list<string>  $excludeFiles
     */
    private function excluded(string $relative, array $excludeDirs, array $excludeFiles): bool
    {
        if (in_array($relative, $excludeFiles, true)) {
            return true;
        }

        foreach ($excludeDirs as $dir) {
            if ($relative === $dir || str_starts_with($relative, $dir.'/')) {
                return true;
            }
        }

        return str_ends_with($relative, '.log')
            || str_contains($relative, '/.git/')
            || str_ends_with($relative, '.sqlite');
    }

    private function instructions(): string
    {
        return <<<'TXT'
SAMS — cómo subir el sistema
============================

1. Sube TODO este ZIP al hosting (descomprímelo en public_html o en la carpeta del dominio).
2. NO reemplaces el archivo .env del servidor (ahí está la conexión a MySQL).
3. Si la base es nueva, importa el SQL en phpMyAdmin.
4. Abre /inicio — los estilos ya van en public/build y public/css/sams.css

Permisos: storage/ y bootstrap/cache/ deben ser escribibles.
No subas public/hot (rompe los estilos).

Si el dominio apunta a la raíz del proyecto (no a public/), el .htaccess de la raíz redirige a public/.
TXT;
    }
}
