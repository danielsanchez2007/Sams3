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
