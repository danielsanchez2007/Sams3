<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Throwable;

class DeployEnvironment
{
    public static function boot(): void
    {
        self::forgetViteHotFile();
        self::ensureStorageLink();
        self::alignPublicUrls();
    }

    public static function isInstalled(): bool
    {
        if (is_file(storage_path('app/installed.json'))) {
            return true;
        }

        if (! is_file(base_path('.env')) || ! filled(config('app.key'))) {
            return false;
        }

        $mysqlReady = config('database.default') === 'mysql'
            && filled(config('database.connections.mysql.database'))
            && filled(config('database.connections.mysql.username'));

        if (! $mysqlReady) {
            return false;
        }

        try {
            return Schema::hasTable('users');
        } catch (Throwable) {
            return app()->environment('production');
        }
    }

    public static function shouldForceInstaller(?Request $request = null): bool
    {
        if (app()->environment('local', 'testing')) {
            return false;
        }

        if (self::isInstalled()) {
            return false;
        }

        $path = $request->path();
        if ($request->is('instalar', 'instalar/*', 'up', 'favicon.ico')
            || str_starts_with($path, 'build/')
            || str_starts_with($path, 'css/')
            || str_starts_with($path, 'js/')
            || str_starts_with($path, 'images/')
            || str_starts_with($path, 'storage/')
            || str_starts_with($path, 'fonts/')) {
            return false;
        }

        return true;
    }

    public static function markInstalled(array $meta = []): void
    {
        $payload = array_merge([
            'installed_at' => now()->toIso8601String(),
            'app_url' => config('app.url'),
        ], $meta);

        File::ensureDirectoryExists(storage_path('app'));
        File::put(
            storage_path('app/installed.json'),
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    public static function forgetViteHotFile(): void
    {
        if (app()->environment('local', 'testing')) {
            return;
        }

        $hot = public_path('hot');
        if (is_file($hot)) {
            @unlink($hot);
        }
    }

    public static function ensureStorageLink(): void
    {
        $link = public_path('storage');
        $target = storage_path('app/public');

        if (file_exists($link) || is_link($link) || is_dir($link)) {
            return;
        }

        File::ensureDirectoryExists($target);

        try {
            if (PHP_OS_FAMILY === 'Windows') {
                @exec('cmd /c mklink /J '.escapeshellarg($link).' '.escapeshellarg($target).' >NUL 2>&1');
            } else {
                @symlink($target, $link);
            }
        } catch (Throwable) {
            // El fallback HTTP está en PublicStorageController.
        }
    }

    public static function databaseLooksReady(): bool
    {
        try {
            return Schema::hasTable('users') && Schema::hasTable('empresas');
        } catch (Throwable) {
            return false;
        }
    }

    private static function alignPublicUrls(): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        try {
            $request = request();
            $root = rtrim($request->getSchemeAndHttpHost().$request->getBasePath(), '/');
            if ($root !== '') {
                URL::forceRootUrl($root);
                config(['filesystems.disks.public.url' => $root.'/storage']);
            }
            if ($request->isSecure() || str_starts_with((string) config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }
        } catch (Throwable) {
            // ignore
        }
    }
}
