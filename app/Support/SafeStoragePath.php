<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

final class SafeStoragePath
{
    public static function relativeWithinPublic(string $path): ?string
    {
        $normalized = str_replace('\\', '/', $path);
        $normalized = ltrim($normalized, '/');
        $normalized = (string) preg_replace('#^storage/#', '', $normalized);
        $normalized = rawurldecode($normalized);

        if (
            $normalized === ''
            || str_contains($normalized, '..')
            || str_contains($normalized, "\0")
            || str_starts_with($normalized, '/')
        ) {
            return null;
        }

        $disk = Storage::disk('public');
        $base = realpath($disk->path(''));
        if ($base === false) {
            return null;
        }

        $absolute = $disk->path($normalized);
        $real = realpath($absolute);

        if ($real === false) {
            $parent = realpath(dirname($absolute));
            if ($parent === false || ($parent !== $base && ! str_starts_with($parent, $base.DIRECTORY_SEPARATOR))) {
                return null;
            }

            return $normalized;
        }

        if ($real !== $base && ! str_starts_with($real, $base.DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $normalized;
    }

    public static function readPublicBytes(string $path): ?string
    {
        $relative = self::relativeWithinPublic($path);
        if ($relative === null || ! Storage::disk('public')->exists($relative)) {
            return null;
        }

        $absolute = Storage::disk('public')->path($relative);
        if (! is_file($absolute) || ! is_readable($absolute)) {
            return null;
        }

        $bytes = @file_get_contents($absolute);

        return $bytes === false ? null : $bytes;
    }

    public static function toDataUri(string $path): ?string
    {
        $relative = self::relativeWithinPublic($path);
        $bytes = self::readPublicBytes($path);
        if ($relative === null || $bytes === null) {
            return null;
        }

        $absolute = Storage::disk('public')->path($relative);
        $mime = @mime_content_type($absolute) ?: 'application/octet-stream';

        return 'data:'.$mime.';base64,'.base64_encode($bytes);
    }
}
