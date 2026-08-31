<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\EquipoImagen;
use App\Models\EquipoInspeccion;
use App\Models\User;
use App\Services\EmpresaContext;
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

        return Storage::disk('public')->response($relative);
    }

    private function assertTenantCanRead(string $relative): void
    {
        $user = auth()->user();
        abort_unless($user, 403);

        if (! $user->empresa_id) {
            return;
        }

        $empresaId = (int) (EmpresaContext::empresaId() ?: $user->empresa_id);

        $ownerUser = User::query()
            ->where('photo', $relative)
            ->orWhere('signature', $relative)
            ->first();
        if ($ownerUser) {
            abort_unless(
                (int) $ownerUser->id === (int) $user->id
                || (int) $ownerUser->empresa_id === $empresaId,
                403
            );

            return;
        }

        $ownerEmpresa = Empresa::query()
            ->where('logo', $relative)
            ->orWhere('logo_principal', $relative)
            ->orWhere('logo_secundario', $relative)
            ->orWhere('foto_empresa', $relative)
            ->first();
        if ($ownerEmpresa) {
            abort_unless((int) $ownerEmpresa->id === $empresaId, 403);

            return;
        }

        $imagen = EquipoImagen::query()->with('equipo:id,empresa_id')->where('path', $relative)->first();
        if ($imagen?->equipo) {
            $eqEmpresa = $imagen->equipo->empresa_id;
            abort_unless($eqEmpresa === null || (int) $eqEmpresa === $empresaId, 403);

            return;
        }

        $inspeccion = EquipoInspeccion::query()->with('equipo:id,empresa_id')->where('pdf_path', $relative)->first();
        if ($inspeccion?->equipo) {
            $eqEmpresa = $inspeccion->equipo->empresa_id;
            abort_unless($eqEmpresa === null || (int) $eqEmpresa === $empresaId, 403);
        }
    }
}
