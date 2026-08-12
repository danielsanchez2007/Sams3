<?php

namespace App\Services;

use App\Models\User;

/**
 * Vista reducida "oficina" (rol adminoficina o administrador en modo oficina por sesión).
 */
final class VistaOficina
{
    public const SESSION_MODO_OFICINA = 'sams_modo_oficina';

    public static function esAdministradorGlobal(?User $user): bool
    {
        return (bool) $user
            && !$user->empresa_id
            && strtolower(trim($user->role?->name ?? '')) === 'administrador';
    }

    public static function esRolAdminOficina(?User $user): bool
    {
        return (bool) $user
            && strtolower(trim($user->role?->name ?? '')) === 'adminoficina';
    }

    public static function modoOficinaSesionActivo(): bool
    {
        return (bool) session(self::SESSION_MODO_OFICINA, false);
    }

    /** Misma lógica de menú / permisos que el rol adminoficina. */
    public static function mostrarMenuOficina(?User $user): bool
    {
        return self::esRolAdminOficina($user)
            || (self::esAdministradorGlobal($user) && self::modoOficinaSesionActivo());
    }
}
