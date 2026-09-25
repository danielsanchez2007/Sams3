<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicStorageAuthTest extends TestCase
{
    public function test_storage_images_require_authentication(): void
    {
        $this->get('/storage/users/signatures/sign-1-1.png')
            ->assertStatus(403);

        $this->get('/storage/equipos/archivos/cert.pdf')
            ->assertStatus(403);
    }
}
