<?php

namespace App\Policies;

use App\Models\Empresa;
use App\Models\EquipoImagen;
use App\Models\User;
use App\Services\EmpresaContext;
use App\Support\SensitiveDocumentStorage;

/**
 * Allow-list para PublicStorageController — denegar por defecto.
 */
class PublicFilePolicy
{
    public static function allows(string $relative, User $user): bool
    {
        $relative = str_replace('\\', '/', ltrim($relative, '/'));

        if (SensitiveDocumentStorage::isSensitivePath($relative)) {
            return false;
        }

        if (
            str_starts_with($relative, 'equipos/archivos/')
            || str_starts_with($relative, 'inspeccion/')
            || str_starts_with($relative, 'bajas/pdfs/')
            || str_starts_with($relative, 'hoja_vida/pdf/')
        ) {
            return false;
        }

        if (!$user->empresa_id) {
            return self::allowsGlobalAdmin($relative);
        }

        $empresaId = (int) (EmpresaContext::empresaId() ?: $user->empresa_id);

        return self::allowsTenantPath($relative, $empresaId, $user);
    }

    private static function allowsGlobalAdmin(string $relative): bool
    {
        if (str_starts_with($relative, 'users/') || str_starts_with($relative, 'photos/')) {
            return User::query()->where('photo', $relative)->orWhere('signature', $relative)->exists();
        }

        if (str_starts_with($relative, 'empresas/')) {
            return Empresa::query()
                ->where('logo', $relative)
                ->orWhere('logo_principal', $relative)
                ->orWhere('logo_secundario', $relative)
                ->orWhere('foto_empresa', $relative)
                ->exists();
        }

        if (str_starts_with($relative, 'equipos/')) {
            return EquipoImagen::query()->where('path', $relative)->exists();
        }

        return false;
    }

    private static function allowsTenantPath(string $relative, int $empresaId, User $user): bool
    {
        $ownerUser = User::query()
            ->where(function ($q) use ($relative) {
                $q->where('photo', $relative)->orWhere('signature', $relative);
            })
            ->first();

        if ($ownerUser) {
            return (int) $ownerUser->id === (int) $user->id
                || (int) $ownerUser->empresa_id === $empresaId;
        }

        $ownerEmpresa = Empresa::query()
            ->where(function ($q) use ($relative) {
                $q->where('logo', $relative)
                    ->orWhere('logo_principal', $relative)
                    ->orWhere('logo_secundario', $relative)
                    ->orWhere('foto_empresa', $relative);
            })
            ->first();

        if ($ownerEmpresa) {
            return (int) $ownerEmpresa->id === $empresaId;
        }

        $imagen = EquipoImagen::query()->with('equipo:id,empresa_id')->where('path', $relative)->first();
        if ($imagen?->equipo) {
            $eqEmpresa = $imagen->equipo->empresa_id;

            return $eqEmpresa === null || (int) $eqEmpresa === $empresaId;
        }

        return false;
    }
}
