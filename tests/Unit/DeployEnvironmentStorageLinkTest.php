<?php

namespace Tests\Unit;

use App\Support\DeployEnvironment;
use Tests\TestCase;

class DeployEnvironmentStorageLinkTest extends TestCase
{
    public function test_public_storage_is_not_a_web_junction_after_disable(): void
    {
        DeployEnvironment::disablePublicStorageLink();

        $this->assertFalse(
            DeployEnvironment::isPublicStorageLinkPresent(),
            'public/storage no debe ser symlink/junction tras boot'
        );
        $this->assertFalse(is_link(public_path('storage')));
    }
}
