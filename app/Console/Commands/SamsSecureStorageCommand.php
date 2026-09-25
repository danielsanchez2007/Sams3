<?php

namespace App\Console\Commands;

use App\Support\DeployEnvironment;
use App\Support\SensitiveDocumentStorage;
use Illuminate\Console\Command;

class SamsSecureStorageCommand extends Command
{
    protected $signature = 'sams:secure-storage';

    protected $description = 'Quita public/storage y mueve PII/documentos del disco public al private';

    public function handle(): int
    {
        DeployEnvironment::disablePublicStorageLink();

        if (DeployEnvironment::isPublicStorageLinkPresent()) {
            $this->error('public/storage sigue siendo un junction/symlink. No ejecutes storage:link.');

            return self::FAILURE;
        }

        $moved = SensitiveDocumentStorage::migrateAllLegacyFromPublic();
        $this->info("Archivos sensibles movidos de public a private: {$moved}");
        $this->info('public/storage no es un enlace. /storage pasa por Laravel (auth + tenant).');

        return self::SUCCESS;
    }
}
