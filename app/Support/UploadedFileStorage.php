<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadedFileStorage
{
    /** @var array<string, list<string>> */
    private const MIME_EXTENSIONS = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
        'application/pdf' => ['pdf'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
        'application/vnd.ms-excel' => ['xls'],
    ];

    /**
     * Guarda un archivo subido en el disco public.
     * Evita UploadedFile::store(), que falla en Windows cuando getRealPath() devuelve vacío.
     *
     * @param  list<string>|null  $allowedMimes
     * @throws \RuntimeException
     */
    public static function storePublic(UploadedFile $file, string $directory, ?array $allowedMimes = null): string
    {
        if (!$file->isValid()) {
            throw new \RuntimeException('El archivo no se recibió correctamente.');
        }

        $tmpPath = $file->getRealPath() ?: $file->getPathname();
        if (!$tmpPath || !is_file($tmpPath)) {
            throw new \RuntimeException('No se pudo leer el archivo temporal de la subida.');
        }

        $contenido = file_get_contents($tmpPath);
        if ($contenido === false || $contenido === '') {
            throw new \RuntimeException('El archivo llegó vacío.');
        }

        $mime = self::detectMime($tmpPath, $file);
        $allowedMimes ??= ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($mime, $allowedMimes, true)) {
            throw new \RuntimeException('Tipo de archivo no permitido.');
        }

        $extension = self::extensionForMime($mime);
        if ($extension === null) {
            throw new \RuntimeException('No se pudo determinar la extensión del archivo.');
        }

        $filename = Str::random(40) . '.' . $extension;
        $path = rtrim($directory, '/') . '/' . $filename;

        Storage::disk('public')->put($path, $contenido);

        return $path;
    }

    public static function deletePublic(?string $path): void
    {
        if (!$path) {
            return;
        }

        try {
            Storage::disk('public')->delete($path);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function storePublicImage(UploadedFile $file, string $directory): string
    {
        return self::storePublic($file, $directory, ['image/jpeg', 'image/png', 'image/webp']);
    }

    public static function storePublicSpreadsheet(UploadedFile $file, string $directory): string
    {
        return self::storePublic($file, $directory, [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
        ]);
    }

    private static function detectMime(string $tmpPath, UploadedFile $file): string
    {
        $mime = '';
        if (function_exists('mime_content_type')) {
            $detected = @mime_content_type($tmpPath);
            if (is_string($detected) && $detected !== '') {
                $mime = $detected;
            }
        }

        if ($mime === '' && class_exists(\finfo::class)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $detected = $finfo->file($tmpPath);
            if (is_string($detected) && $detected !== '') {
                $mime = $detected;
            }
        }

        if ($mime === '') {
            $mime = (string) $file->getMimeType();
        }

        return strtolower(trim(explode(';', $mime)[0]));
    }

    private static function extensionForMime(string $mime): ?string
    {
        $extensions = self::MIME_EXTENSIONS[$mime] ?? null;

        return $extensions[0] ?? null;
    }
}
