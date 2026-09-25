<?php

namespace App\Policies;

use App\Models\AvisoEmpresa;
use App\Models\Empresa;
use App\Models\EquipoImagen;
use App\Models\User;
use App\Services\EmpresaContext;
use App\Support\SensitiveDocumentStorage;

/**
 * Allow-list para PublicStorageController — denegar por defecto.
 * Imágenes de PII/tenant se sirven autenticadas; PDFs y ofimática nunca por esta ruta.
 */
class PublicFilePolicy
{
    /** @var list<string> */
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public static function allows(string $relative, User $user): bool
    {
        $relative = str_replace('\\', '/', ltrim($relative, '/'));

        if (!self::isPublicImage($relative)) {
            return false;
        }

        if (self::isOwnMedia($relative, $user)) {
            return true;
        }

        $empresaId = (int) (EmpresaContext::resolveId() ?: $user->empresa_id ?: 0);

        if ($empresaId > 0) {
            return self::allowsTenantPath($relative, $empresaId, $user);
        }

        if (!$user->empresa_id) {
            return self::allowsGlobalAdmin($relative);
        }

        return false;
    }

    private static function isPublicImage(string $relative): bool
    {
        $ext = strtolower((string) pathinfo($relative, PATHINFO_EXTENSION));

        return in_array($ext, self::IMAGE_EXTENSIONS, true);
    }

    private static function isOwnMedia(string $relative, User $user): bool
    {
        $photo = trim((string) $user->photo);
        $signature = trim((string) $user->signature);

        return ($photo !== '' && $photo === $relative)
            || ($signature !== '' && $signature === $relative);
    }

    private static function allowsGlobalAdmin(string $relative): bool
    {
        if (str_starts_with($relative, 'seed/placeholders/')) {
            return true;
        }

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

        if (str_starts_with($relative, 'aviso_cumplimientos/')) {
            return AvisoEmpresa::query()->where('imagen_cumplimiento_path', $relative)->exists();
        }

        return false;
    }

    private static function allowsTenantPath(string $relative, int $empresaId, User $user): bool
    {
        if (str_starts_with($relative, 'seed/placeholders/')) {
            return true;
        }

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

        if (str_starts_with($relative, 'aviso_cumplimientos/')) {
            $aviso = AvisoEmpresa::query()->where('imagen_cumplimiento_path', $relative)->first();

            return $aviso !== null && (int) $aviso->empresa_id === $empresaId;
        }

        if (SensitiveDocumentStorage::isSensitivePath($relative)) {
            return false;
        }

        return false;
    }
}
