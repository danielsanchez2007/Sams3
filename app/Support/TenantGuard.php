<?php

namespace App\Support;

use App\Models\ClaseEquipo;
use App\Models\Equipo;
use App\Models\EquipoBaja;
use App\Models\EquipoInspeccion;
use App\Services\EmpresaModuleAuthorization;

/**
 * Verificación centralizada de aislamiento multi-tenant (anti-IDOR).
 */
final class TenantGuard
{
    public static function assertOwns(?int $resourceEmpresaId): void
    {
        if ($resourceEmpresaId === null) {
            return;
        }

        (new EmpresaModuleAuthorization())->assertTenantOwns((int) $resourceEmpresaId);
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
