<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * PDFs sensibles (inspección, bajas, hoja de vida) en disco private — no expuestos vía storage:link.
 */
class SensitiveDocumentStorage
{
    public const DISK = 'private';

    /** @var list<string> */
    public const SENSITIVE_PREFIXES = [
        'inspeccion/pdf/',
        'bajas/pdfs/',
        'hoja_vida/pdf/',
    ];

    public static function isSensitivePath(string $path): bool
    {
        $normalized = str_replace('\\', '/', ltrim($path, '/'));

        foreach (self::SENSITIVE_PREFIXES as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                return true;
            }
        }

        return false;
    }

    public static function diskFor(string $path): string
    {
        if (Storage::disk(self::DISK)->exists($path)) {
            return self::DISK;
        }

        if (Storage::disk('public')->exists($path)) {
            return 'public';
        }

        return self::DISK;
    }

    public static function exists(string $path): bool
    {
        if (Storage::disk(self::DISK)->exists($path)) {
            return true;
        }

        if (Storage::disk('public')->exists($path) && self::isSensitivePath($path)) {
            self::migrateLegacyFromPublic($path);

            return Storage::disk(self::DISK)->exists($path);
        }

        return Storage::disk('public')->exists($path);
    }

    /**
     * Mueve PDFs legacy desde public → private al primer acceso autenticado.
     */
    public static function migrateLegacyFromPublic(string $path): void
    {
        if (!self::isSensitivePath($path)) {
            return;
        }

        if (!Storage::disk('public')->exists($path) || Storage::disk(self::DISK)->exists($path)) {
            return;
        }

        try {
            $contents = Storage::disk('public')->get($path);
            if ($contents !== null && $contents !== '') {
                self::put($path, $contents);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function size(string $path): int
    {
        $disk = self::diskFor($path);

        return (int) (Storage::disk($disk)->size($path) ?? 0);
    }

    public static function path(string $path): string
    {
        return Storage::disk(self::diskFor($path))->path($path);
    }

    public static function makeDirectory(string $directory): void
    {
        Storage::disk(self::DISK)->makeDirectory($directory);
    }

    public static function put(string $path, string $contents): void
    {
        $directory = dirname($path);
        if ($directory !== '.' && $directory !== '') {
            self::makeDirectory($directory);
        }

        Storage::disk(self::DISK)->put($path, $contents);

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public static function delete(?string $path): void
    {
        if (!$path) {
            return;
        }

        foreach ([self::DISK, 'public'] as $disk) {
            try {
                if (Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->delete($path);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /** @return array<string, string> */
    public static function secureHeaders(bool $attachment = true): array
    {
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => 'sandbox',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ];

        if ($attachment) {
            $headers['Content-Disposition'] = 'attachment';
        }

        return $headers;
    }

    public static function download(string $path, string $downloadName): StreamedResponse
    {
        $disk = self::diskFor($path);

        return Storage::disk($disk)->download($path, $downloadName, self::secureHeaders(true));
    }

    /**
     * @param  array<string, string>  $extra
     */
    public static function inlineFileResponse(string $path, array $extra = [])
    {
        $disk = self::diskFor($path);
        $headers = array_merge([
            'Content-Type' => 'application/pdf',
        ], self::secureHeaders(false), $extra);

        return response()->file(Storage::disk($disk)->path($path), $headers);
    }
}
