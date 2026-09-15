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
        $optimized = null;
        try {
            $optimized = self::optimizeImageContents($file);
        } catch (\Throwable $e) {
            report($e);
        }

        if ($optimized === null) {
            return self::storePublic($file, $directory, ['image/jpeg', 'image/png', 'image/webp']);
        }

        [$contents, $extension] = $optimized;
        $filename = Str::random(40) . '.' . $extension;
        $path = rtrim($directory, '/') . '/' . $filename;
        Storage::disk('public')->put($path, $contents);

        return $path;
    }

    /**
     * Reduce fotos grandes para que quepan y ocupen menos.
     * Si GD no está, la resolución es enorme o falla, retorna null y se guarda el original.
     *
     * @return array{0: string, 1: string}|null
     */
    private static function optimizeImageContents(UploadedFile $file): ?array
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagejpeg')) {
            return null;
        }

        $tmpPath = $file->getRealPath() ?: $file->getPathname();
        if (! $tmpPath || ! is_file($tmpPath)) {
            return null;
        }

        $info = @getimagesize($tmpPath);
        if (! is_array($info) || empty($info[0]) || empty($info[1])) {
            return null;
        }

        $srcW = (int) $info[0];
        $srcH = (int) $info[1];
        // Un JPEG de pocos MB puede decodificar a decenas de millones de píxeles y tumbar PHP.
        if ($srcW * $srcH > 6_000_000 || $srcW > 4500 || $srcH > 4500) {
            return null;
        }

        $mime = self::detectMime($tmpPath, $file);
        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmpPath),
            'image/png' => @imagecreatefrompng($tmpPath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmpPath) : false,
            default => false,
        };
        if ($source === false) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $maxEdge = 1920;
        $scale = 1.0;
        if ($width > $maxEdge || $height > $maxEdge) {
            $scale = $maxEdge / max($width, $height);
        }

        $target = $source;
        if ($scale < 1) {
            $newW = max(1, (int) round($width * $scale));
            $newH = max(1, (int) round($height * $scale));
            $resized = imagecreatetruecolor($newW, $newH);
            if ($resized === false) {
                imagedestroy($source);

                return null;
            }
            $white = imagecolorallocate($resized, 255, 255, 255);
            imagefilledrectangle($resized, 0, 0, $newW, $newH, $white);
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $newW, $newH, $width, $height);
            imagedestroy($source);
            $target = $resized;
        } elseif ($mime === 'image/png' || $mime === 'image/webp') {
            $flattened = imagecreatetruecolor($width, $height);
            if ($flattened === false) {
                imagedestroy($source);

                return null;
            }
            $white = imagecolorallocate($flattened, 255, 255, 255);
            imagefilledrectangle($flattened, 0, 0, $width, $height, $white);
            imagecopy($flattened, $source, 0, 0, 0, 0, $width, $height);
            imagedestroy($source);
            $target = $flattened;
        }

        ob_start();
        $ok = imagejpeg($target, null, 85);
        $binary = (string) ob_get_clean();
        imagedestroy($target);

        if (! $ok || $binary === '') {
            return null;
        }

        return [$binary, 'jpg'];
    }

    public static function storePublicSpreadsheet(UploadedFile $file, string $directory): string
    {
        return self::storePublic($file, $directory, [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
        ]);
    }

    /**
     * Documentos adjuntos (no imagen): MIME real + extensión allowlist.
     *
     * @throws \RuntimeException
     */
    public static function storePublicDocument(UploadedFile $file, string $directory): string
    {
        $allowedMimes = [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
        ];

        $path = self::storePublic($file, $directory, $allowedMimes);
        self::assertAllowedExtension($file, $path);

        return $path;
    }

    /** @return array<string, string> */
    public static function secureDownloadHeaders(): array
    {
        return [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => 'sandbox',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ];
    }

    private static function assertAllowedExtension(UploadedFile $file, string $storedPath): void
    {
        $storedExt = strtolower(pathinfo($storedPath, PATHINFO_EXTENSION));
        $originalExt = strtolower(pathinfo((string) $file->getClientOriginalName(), PATHINFO_EXTENSION));

        $allowed = ['pdf', 'docx', 'xlsx', 'xls'];
        if (!in_array($storedExt, $allowed, true) || ($originalExt !== '' && !in_array($originalExt, $allowed, true))) {
            self::deletePublic($storedPath);
            throw new \RuntimeException('Extensión de archivo no permitida.');
        }
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
