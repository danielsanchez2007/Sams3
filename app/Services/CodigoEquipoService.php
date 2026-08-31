<?php

namespace App\Services;

use App\Models\CodigoReutilizable;
use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\TipoEquipo;
use Illuminate\Support\Facades\DB;

class CodigoEquipoService
{
    public static function normalizar(string $codigo): string
    {
        return strtoupper(trim($codigo));
    }

    public static function esInventario(string $codigo): bool
    {
        $c = self::normalizar($codigo);
        return $c !== '' && (str_starts_with($c, 'IN-') || str_contains($c, '-IN-'));
    }

    public static function esMaterial(string $codigo): bool
    {
        $c = self::normalizar($codigo);
        return $c !== '' && (str_starts_with($c, 'MD-') || str_contains($c, '-MD-'));
    }

    public static function esAuditoria(string $codigo): bool
    {
        $c = self::normalizar($codigo);
        return $c !== '' && (str_starts_with($c, 'AUD-') || str_contains($c, '-AUD-'));
    }

    public static function esBaja(string $codigo): bool
    {
        $c = self::normalizar($codigo);
        return $c !== '' && (str_starts_with($c, 'DB-') || str_contains($c, '-DB-'));
    }

    /**
     * Normaliza un código de inventario para el pool de reutilización:
     * quita el sufijo de nombre "-(NOMBRE)" y deja PREF-ALIAS-IN-NNNN.
     */
    public static function codigoInventarioBase(string $codigo): string
    {
        $codigo = self::normalizar($codigo);
        if ($codigo === '') {
            return '';
        }

        // PREF-XXX-IN-0001-(NOMBRE) → PREF-XXX-IN-0001
        if (preg_match('/^(.+-IN-\d{1,6})(?:-\([^)]*\))?$/i', $codigo, $m)) {
            return strtoupper($m[1]);
        }
        // Legado IN-0001-(NOMBRE)
        if (preg_match('/^(IN-\d{1,})(?:-\([^)]*\))?$/i', $codigo, $m)) {
            return strtoupper($m[1]);
        }

        return $codigo;
    }

    /** Libera un código de inventario al pool de reutilización (legado IN- o PREF-…-IN-…). */
    public static function liberarInventario(string $codigo): void
    {
        $codigo = self::codigoInventarioBase($codigo);
        if ($codigo === '' || !self::esInventario($codigo)) {
            return;
        }

        CodigoReutilizable::query()->updateOrCreate(
            ['codigo' => $codigo],
            [
                'estado' => 'disponible',
                'reservado_por' => null,
                'reservado_en' => null,
                'usado_en' => null,
            ]
        );
    }

    public static function empresaTag(?int $empresaId, string $key, string $default): string
    {
        if (!$empresaId) {
            return $default;
        }
        $empresa = Empresa::find($empresaId);
        $tag = strtoupper((string) ($empresa?->code_settings[$key] ?? $default));
        $tag = preg_replace('/[^A-Z0-9]/', '', $tag) ?: $default;

        return $tag;
    }

    public static function aliasTipo(?TipoEquipo $tipo, string $fallback = 'GEN'): string
    {
        $aliasRaw = (string) ($tipo?->alias ?: $tipo?->nombre ?: $fallback);
        return strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $aliasRaw) ?: $fallback, 0, 4));
    }

    public static function nextCodigoMaterial(Equipo $equipo, ?int $tipoDidacticoId = null): string
    {
        $prefijo = EmpresaContext::prefijo() ?: 'GEN';
        $tag = self::empresaTag($equipo->empresa_id, 'material_tag', 'MD');
        $tipo = $tipoDidacticoId ? TipoEquipo::find($tipoDidacticoId) : $equipo->tipoEquipo;
        $alias = self::aliasTipo($tipo);
        $patron = $prefijo . '-' . $alias . '-' . $tag . '-%';

        $maxEquipo = (int) DB::table('equipos')
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(codigo, '-', -1) AS UNSIGNED)) as max_num")
            ->whereNotNull('codigo')
            ->where(function ($q) use ($patron, $tag) {
                $q->where('codigo', 'like', $patron)
                    ->orWhere('codigo', 'like', '%-' . $tag . '-%')
                    ->orWhere('codigo', 'like', $tag . '-%');
            })
            ->value('max_num');

        $maxTraspaso = 0;
        if (DB::getSchemaBuilder()->hasTable('material_didactico_traspasos')) {
            $maxTraspaso = (int) DB::table('material_didactico_traspasos')
                ->selectRaw("MAX(CAST(SUBSTRING_INDEX(codigo_md, '-', -1) AS UNSIGNED)) as max_num")
                ->whereNotNull('codigo_md')
                ->where(function ($q) use ($patron, $tag) {
                    $q->where('codigo_md', 'like', $patron)
                        ->orWhere('codigo_md', 'like', '%-' . $tag . '-%')
                        ->orWhere('codigo_md', 'like', $tag . '-%');
                })
                ->value('max_num');
        }

        $next = max($maxEquipo, $maxTraspaso) + 1;

        return $prefijo . '-' . $alias . '-' . $tag . '-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public static function nextCodigoAuditoria(Equipo $equipo): string
    {
        $prefijo = EmpresaContext::prefijo() ?: 'GEN';
        $tag = self::empresaTag($equipo->empresa_id, 'auditoria_tag', 'AUD');
        $alias = self::aliasTipo($equipo->tipoEquipo);
        $patron = $prefijo . '-' . $alias . '-' . $tag . '-%';

        $maxEquipo = (int) DB::table('equipos')
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(codigo, '-', -1) AS UNSIGNED)) as max_num")
            ->whereNotNull('codigo')
            ->where(function ($q) use ($patron, $tag) {
                $q->where('codigo', 'like', $patron)
                    ->orWhere('codigo', 'like', '%-' . $tag . '-%')
                    ->orWhere('codigo', 'like', $tag . '-%');
            })
            ->value('max_num');

        $maxTraspaso = 0;
        if (DB::getSchemaBuilder()->hasTable('equipos_auditoria_traspasos')) {
            $maxTraspaso = (int) DB::table('equipos_auditoria_traspasos')
                ->selectRaw("MAX(CAST(SUBSTRING_INDEX(codigo_aud, '-', -1) AS UNSIGNED)) as max_num")
                ->whereNotNull('codigo_aud')
                ->where(function ($q) use ($patron, $tag) {
                    $q->where('codigo_aud', 'like', $patron)
                        ->orWhere('codigo_aud', 'like', '%-' . $tag . '-%')
                        ->orWhere('codigo_aud', 'like', $tag . '-%');
                })
                ->value('max_num');
        }

        $next = max($maxEquipo, $maxTraspaso) + 1;

        return $prefijo . '-' . $alias . '-' . $tag . '-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /** Toma un código reutilizable disponible de inventario o genera el siguiente IN de la empresa. */
    public static function nextCodigoInventario(Equipo $equipo): string
    {
        $prefijo = EmpresaContext::prefijo() ?: 'GEN';
        $tag = self::empresaTag($equipo->empresa_id, 'inventory_tag', 'IN');

        $reutilizable = CodigoReutilizable::query()
            ->where('estado', 'disponible')
            ->where(function ($q) use ($prefijo, $tag) {
                $q->where('codigo', 'like', $prefijo . '%-' . $tag . '-%')
                    ->orWhere('codigo', 'like', $tag . '-%');
            })
            ->orderBy('id')
            ->first();

        if ($reutilizable) {
            $reutilizable->update([
                'estado' => 'usado',
                'reservado_por' => null,
                'reservado_en' => null,
                'usado_en' => now(),
            ]);

            return $reutilizable->codigo;
        }

        $alias = self::aliasTipo($equipo->tipoEquipo);
        $tag = strtoupper((string) preg_replace('/[^A-Z0-9]/', '', $tag));
        if ($tag === '') {
            $tag = 'EQ';
        }
        $patron = $prefijo . '-%-' . $tag . '-%';

        $likeTag = '%-'.$tag.'-%';
        $likePrefix = $tag.'-%';

        $maxEquipo = (int) DB::table('equipos')
            ->selectRaw("MAX(CAST(
                CASE
                    WHEN codigo LIKE ? THEN SUBSTRING_INDEX(SUBSTRING_INDEX(codigo, ?, -1), '-', 1)
                    WHEN codigo LIKE ? THEN SUBSTRING_INDEX(codigo, '-', -1)
                    ELSE 0
                END AS UNSIGNED
            )) as max_num", [$likeTag, '-'.$tag.'-', $likePrefix])
            ->whereNotNull('codigo')
            ->where(function ($q) use ($patron, $tag) {
                $q->where('codigo', 'like', $patron)
                    ->orWhere('codigo', 'like', $tag . '-%');
            })
            ->value('max_num');

        $maxPool = (int) CodigoReutilizable::query()
            ->selectRaw("MAX(CAST(
                CASE
                    WHEN codigo LIKE ? THEN SUBSTRING_INDEX(SUBSTRING_INDEX(codigo, ?, -1), '-', 1)
                    WHEN codigo LIKE ? THEN SUBSTRING_INDEX(codigo, '-', -1)
                    ELSE 0
                END AS UNSIGNED
            )) as max_num", [$likeTag, '-'.$tag.'-', $likePrefix])
            ->where(function ($q) use ($patron, $tag) {
                $q->where('codigo', 'like', $patron)
                    ->orWhere('codigo', 'like', $tag . '-%');
            })
            ->value('max_num');

        $next = max($maxEquipo, $maxPool) + 1;

        return $prefijo . '-' . $alias . '-' . $tag . '-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
