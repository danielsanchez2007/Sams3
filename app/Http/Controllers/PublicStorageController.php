<?php

namespace App\Http\Controllers;

use App\Policies\PublicFilePolicy;
use App\Support\SafeStoragePath;
use Illuminate\Support\Facades\Storage;

class PublicStorageController extends Controller
{
    public function show(string $path)
    {
        $relative = SafeStoragePath::relativeWithinPublic($path);
        abort_unless($relative !== null, 404);
        abort_unless(Storage::disk('public')->exists($relative), 404);

        $this->assertTenantCanRead($relative);

        return Storage::disk('public')->response($relative, null, $this->securePublicHeaders());
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
