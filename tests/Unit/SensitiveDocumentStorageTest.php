<?php

namespace Tests\Unit;

use App\Support\SensitiveDocumentStorage;
use Tests\TestCase;

class SensitiveDocumentStorageTest extends TestCase
{
    public function test_sensitive_prefixes_cover_documents_and_pii_images(): void
    {
        $this->assertTrue(SensitiveDocumentStorage::isSensitivePath('hoja_vida/excel/a.xlsx'));
        $this->assertTrue(SensitiveDocumentStorage::isSensitivePath('hoja_vida/pdf/a.pdf'));
        $this->assertTrue(SensitiveDocumentStorage::isSensitivePath('inspeccion/plantillas/a.xlsx'));
        $this->assertTrue(SensitiveDocumentStorage::isSensitivePath('bajas/plantillas/a.html'));
        $this->assertTrue(SensitiveDocumentStorage::isSensitivePath('equipos/archivos/cert.pdf'));
        $this->assertTrue(SensitiveDocumentStorage::isSensitivePath('formatos/plantilla.xlsx'));
        $this->assertTrue(SensitiveDocumentStorage::isSensitivePath('equipos/fotos/a.jpg'));
        $this->assertTrue(SensitiveDocumentStorage::isSensitivePath('equipos/imagenes/a.jpg'));
        $this->assertTrue(SensitiveDocumentStorage::isSensitivePath('users/photos/a.jpg'));
        $this->assertTrue(SensitiveDocumentStorage::isSensitivePath('users/signatures/a.png'));
        $this->assertTrue(SensitiveDocumentStorage::isSensitivePath('empresas/logos/a.png'));
        $this->assertTrue(SensitiveDocumentStorage::isSensitivePath('aviso_cumplimientos/a.jpg'));
        $this->assertTrue(SensitiveDocumentStorage::isSensitivePath('photos/legacy.jpg'));
    }

    public function test_exists_does_not_require_private_copy(): void
    {
        $this->assertFalse(SensitiveDocumentStorage::exists('hoja_vida/excel/missing-'.uniqid('', true).'.xlsx'));
    }

    public function test_legacy_public_file_is_moved_to_private(): void
    {
        $path = 'users/photos/migrate-test-'.uniqid('', true).'.png';
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMB/axH4i0AAAAASUVORK5CYII=');

        \Illuminate\Support\Facades\Storage::disk('public')->put($path, $png);
        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('public')->exists($path));

        SensitiveDocumentStorage::migrateLegacyFromPublic($path);

        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('private')->exists($path));
        $this->assertFalse(\Illuminate\Support\Facades\Storage::disk('public')->exists($path));

        SensitiveDocumentStorage::delete($path);
    }
}
