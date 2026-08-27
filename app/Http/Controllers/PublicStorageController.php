<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class PublicStorageController extends Controller
{
    public function show(string $path)
    {
        $normalized = str_replace('\\', '/', $path);
        $normalized = ltrim($normalized, '/');

        if (
            $normalized === ''
            || str_contains($normalized, '..')
            || str_contains($normalized, "\0")
            || str_starts_with($normalized, '/')
        ) {
            abort(404);
        }

        abort_unless(Storage::disk('public')->exists($normalized), 404);

        return Storage::disk('public')->response($normalized);
    }
}
