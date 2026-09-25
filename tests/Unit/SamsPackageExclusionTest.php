<?php

namespace Tests\Unit;

use App\Console\Commands\SamsPackageCommand;
use Tests\TestCase;

class SamsPackageExclusionTest extends TestCase
{
    public function test_package_excludes_storage_pii_and_sql_dumps(): void
    {
        $this->assertTrue(SamsPackageCommand::pathIsExcluded('public/storage/users/signatures/a.png'));
        $this->assertTrue(SamsPackageCommand::pathIsExcluded('storage/app/private/exports/dump.sql'));
        $this->assertTrue(SamsPackageCommand::pathIsExcluded('storage/app/public/users/signatures/a.png'));
        $this->assertTrue(SamsPackageCommand::pathIsExcluded('storage/app/public/aviso_cumplimientos/x.jpg'));
        $this->assertTrue(SamsPackageCommand::pathIsExcluded('backup.sql'));
        $this->assertTrue(SamsPackageCommand::pathIsExcluded('.env'));
        $this->assertFalse(SamsPackageCommand::pathIsExcluded('app/Http/Controllers/ProfileController.php'));
        $this->assertFalse(SamsPackageCommand::pathIsExcluded('database/migrations/2024_01_01_000000_create_users_table.php'));
    }
}
