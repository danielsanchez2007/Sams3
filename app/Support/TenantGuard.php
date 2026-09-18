<?php

namespace App\Support;

use App\Models\ClaseEquipo;
use App\Models\Equipo;
use App\Models\EquipoBaja;
use App\Models\EquipoInspeccion;
use App\Services\EmpresaContext;

/**
 * Aislamiento multi-tenant alineado con EquipoPolicy (anti-IDOR).
 */
final class TenantGuard
{
    public static function assertOwns(?int $resourceEmpresaId): void
    {
        abort_unless(auth()->check(), 403);

        $activa = EmpresaContext::resolveId();
        if (!$activa) {
            abort(403, 'Debes seleccionar una empresa activa.');
        }

        if ($resourceEmpresaId === null) {
            return;
        }

        if ((int) $activa !== (int) $resourceEmpresaId) {
            abort(403, 'No tienes permiso para acceder a recursos de otra empresa.');
        }
    }

    public static function assertEquipo(Equipo $equipo): void
    {
        self::assertOwns($equipo->empresa_id ? (int) $equipo->empresa_id : null);
    }

    public static function assertInspeccion(EquipoInspeccion $inspeccion): void
    {
        $inspeccion->loadMissing('equipo');
        if (!$inspeccion->equipo) {
            abort(404);
        }

        self::assertEquipo($inspeccion->equipo);
    }

    public static function assertClaseEquipo(ClaseEquipo $clase): void
    {
        self::assertOwns($clase->empresa_id ? (int) $clase->empresa_id : null);
    }

    public static function assertBaja(EquipoBaja $baja): void
    {
        $baja->loadMissing('equipo');
        if (!$baja->equipo) {
            abort(404);
        }

        self::assertEquipo($baja->equipo);
    }
}
