<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Throwable;

class DeployEnvironment
{
    public static function boot(): void
    {
        self::forgetViteHotFile();
        self::ensureStorageLink();
        self::disablePublicStorageLink();
        self::ensurePublicDocumentDeny();
        self::ensurePrivateDocumentDirs();
        SensitiveDocumentStorage::migrateAllLegacyFromPublic();
        self::alignPublicUrls();
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
        File::ensureDirectoryExists(storage_path('app/public'));
        File::ensureDirectoryExists(storage_path('app/private'));
    }

    /**
     * Quita el enlace public/storage si es un junction/symlink, para que /storage/*
     * pase por PublicStorageController (sesión + tenant).
     *
     * Nunca borra el contenido de storage/app/public: solo el reparse point.
     */
    public static function disablePublicStorageLink(): void
    {
        $link = public_path('storage');
        if (!file_exists($link) && !is_link($link)) {
            return;
        }

        if (!self::isPublicStorageLinkPresent()) {
            Log::warning('public/storage existe como carpeta real. Apache/nginx deben reescribir /storage hacia index.php; no se borra el directorio.');

            return;
        }

        $removed = self::removeStorageLink($link);
        if ($removed && !self::isPublicStorageLinkPresent()) {
            return;
        }

        $message = 'No se pudo quitar el enlace public/storage. Mientras exista, el servidor web puede servir /storage/* sin autenticación.';
        Log::error($message, ['path' => $link]);
        throw new \RuntimeException($message);
    }

    public static function isPublicStorageLinkPresent(): bool
    {
        $link = public_path('storage');
        if (is_link($link)) {
            return true;
        }

        return self::isWindowsReparsePoint($link);
    }

    public static function ensurePublicDocumentDeny(): void
    {
        $htaccess = storage_path('app/public/.htaccess');
        $contents = <<<'HTACCESS'
# Defensa en profundidad: si un junction/symlink sigue vivo, Apache no sirve nada de este disco.
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Deny from all
</IfModule>
HTACCESS;

        try {
            if (!is_file($htaccess) || trim((string) file_get_contents($htaccess)) !== trim($contents)) {
                File::put($htaccess, $contents);
            }
        } catch (Throwable $e) {
            Log::error('No se pudo escribir storage/app/public/.htaccess', ['exception' => $e->getMessage()]);
            report($e);
        }
    }

    public static function ensurePrivateDocumentDirs(): void
    {
        foreach (SensitiveDocumentStorage::SENSITIVE_PREFIXES as $prefix) {
            File::ensureDirectoryExists(storage_path('app/private/' . rtrim($prefix, '/')));
        }
    }

    private static function removeStorageLink(string $link): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $quoted = '"'.str_replace(['"', "\0"], '', $link).'"';
            exec('cmd /c rmdir '.$quoted, $output, $code);

            return $code === 0 && !is_link($link) && !self::isWindowsReparsePoint($link);
        }

        if (is_link($link)) {
            return unlink($link);
        }

        return false;
    }

    private static function isWindowsReparsePoint(string $path): bool
    {
        if (PHP_OS_FAMILY !== 'Windows' || !is_dir($path)) {
            return false;
        }

        $quoted = '"'.str_replace(['"', "\0"], '', $path).'"';
        exec('fsutil reparsepoint query '.$quoted, $output, $code);

        return $code === 0;
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
