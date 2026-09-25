<?php

namespace App\Http\Controllers;

use App\Policies\PublicFilePolicy;
use App\Support\SafeStoragePath;
use App\Support\SensitiveDocumentStorage;
use Illuminate\Support\Facades\Storage;

class PublicStorageController extends Controller
{
    public function show(string $path)
    {
        $relative = SafeStoragePath::relative($path);
        abort_unless($relative !== null, 404);

        if (SensitiveDocumentStorage::isSensitivePath($relative)) {
            SensitiveDocumentStorage::migrateLegacyFromPublic($relative);
        }

        abort_unless(SensitiveDocumentStorage::exists($relative), 404);

        $this->assertTenantCanRead($relative);

        $disk = SensitiveDocumentStorage::diskFor($relative);

        return Storage::disk($disk)->response($relative, null, $this->securePublicHeaders());
    }

    private function assertTenantCanRead(string $relative): void
    {
        $user = auth()->user();
        abort_unless($user, 403);
        abort_unless(PublicFilePolicy::allows($relative, $user), 403);
    }

    /** @return array<string, string> */
    private function securePublicHeaders(): array
    {
        return [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ];
    }
}
