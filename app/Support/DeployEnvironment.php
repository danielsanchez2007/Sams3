<?php

namespace App\Support;

use App\Support\SensitiveDocumentStorage;
use Illuminate\Support\Facades\File;
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
    }

    /**
     * Quita el enlace public/storage si es un junction/symlink, para que /storage/*
     * pase por PublicStorageController (sesión requerida).
     */
    public static function disablePublicStorageLink(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $link = public_path('storage');
        if (!file_exists($link) && !is_link($link)) {
            return;
        }

        try {
            if (PHP_OS_FAMILY === 'Windows') {
                if (is_dir($link)) {
                    @exec('cmd /c rmdir '.escapeshellarg($link).' >NUL 2>&1');
                }
            } elseif (is_link($link)) {
                @unlink($link);
            }
        } catch (Throwable) {
            // Si no se puede quitar, los documentos ya no viven en disco public.
        }
    }

    public static function ensurePublicDocumentDeny(): void
    {
        $htaccess = storage_path('app/public/.htaccess');
        $contents = <<<'HTACCESS'
<FilesMatch "(?i)\.(pdf|xlsx|xls|docx|html)$">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Deny from all
    </IfModule>
</FilesMatch>
HTACCESS;

        try {
            if (!is_file($htaccess) || trim((string) file_get_contents($htaccess)) !== trim($contents)) {
                File::put($htaccess, $contents);
            }
        } catch (Throwable) {
            // ignore
        }
    }

    public static function ensurePrivateDocumentDirs(): void
    {
        foreach (SensitiveDocumentStorage::SENSITIVE_PREFIXES as $prefix) {
            File::ensureDirectoryExists(storage_path('app/private/' . rtrim($prefix, '/')));
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
