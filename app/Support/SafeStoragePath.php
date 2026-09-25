<?php

namespace App\Support;

final class SafeStoragePath
{
    public static function relative(string $path): ?string
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

        return $normalized;
    }

    public static function relativeWithinPublic(string $path): ?string
    {
        return self::relative($path);
    }

    public static function readPublicBytes(string $path): ?string
    {
        $relative = self::relative($path);
        if ($relative === null) {
            return null;
        }

        return SensitiveDocumentStorage::get($relative);
    }

    public static function toDataUri(string $path): ?string
    {
        $relative = self::relative($path);
        $bytes = self::readPublicBytes($path);
        if ($relative === null || $bytes === null) {
            return null;
        }

        $absolute = SensitiveDocumentStorage::fileAbsolutePath($relative);
        $mime = ($absolute && is_file($absolute))
            ? (@mime_content_type($absolute) ?: 'application/octet-stream')
            : 'application/octet-stream';

        return 'data:'.$mime.';base64,'.base64_encode($bytes);
    }
}
