<?php

namespace Tests\Unit;

use App\Support\SensitiveDocumentStorage;
use Tests\TestCase;

class SensitiveDocumentStorageTest extends TestCase
{
    public function test_sensitive_prefixes_cover_documents_not_photos(): void
    {
        $this->assertTrue(SensitiveDocumentStorage::isSensitivePath('hoja_vida/excel/a.xlsx'));
        $this->assertTrue(SensitiveDocumentStorage::isSensitivePath('hoja_vida/pdf/a.pdf'));
        $this->assertTrue(SensitiveDocumentStorage::isSensitivePath('inspeccion/plantillas/a.xlsx'));
        $this->assertTrue(SensitiveDocumentStorage::isSensitivePath('bajas/plantillas/a.html'));
        $this->assertTrue(SensitiveDocumentStorage::isSensitivePath('equipos/archivos/cert.pdf'));
        $this->assertTrue(SensitiveDocumentStorage::isSensitivePath('formatos/plantilla.xlsx'));

        $this->assertFalse(SensitiveDocumentStorage::isSensitivePath('equipos/fotos/a.jpg'));
        $this->assertFalse(SensitiveDocumentStorage::isSensitivePath('users/photo.jpg'));
        $this->assertFalse(SensitiveDocumentStorage::isSensitivePath('empresas/1/logo.png'));
    }

    public function test_exists_does_not_require_private_copy(): void
    {
        $this->assertFalse(SensitiveDocumentStorage::exists('hoja_vida/excel/missing-'.uniqid('', true).'.xlsx'));
    }
}
