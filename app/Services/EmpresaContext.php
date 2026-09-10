<?php

namespace App\Services;

use App\Models\Empresa;
use Illuminate\Support\Facades\Cache;

class EmpresaContext
{
    private const SESSION_KEY = 'empresa_activa_id';

    private static ?Empresa $empresaActivaCache = null;

    private static bool $empresaActivaCacheLoaded = false;

    private static ?Empresa $preventionWorldCache = null;

    private static bool $preventionWorldCacheLoaded = false;

    /** Nombre reconocido como empresa matriz del sistema. */
    public static function preventionWorld(): ?Empresa
    {
        if (self::$preventionWorldCacheLoaded) {
            return self::$preventionWorldCache;
        }

        self::$preventionWorldCacheLoaded = true;

        return self::$preventionWorldCache = Cache::remember('sams_empresa_prevention_world', 600, function () {
            return Empresa::query()
                ->where(function ($q) {
                    $q->whereRaw('LOWER(nombre) LIKE ?', ['%prevention world%'])
                        ->orWhereRaw('LOWER(nombre) LIKE ?', ['%preventon world%'])
                        ->orWhereRaw('LOWER(nombre) LIKE ?', ['%preventios world%']);
                })
                ->orderBy('id')
                ->first();
        });
    }

    public static function esPreventionWorld(?Empresa $empresa): bool
    {
        if (!$empresa) {
            return false;
        }
        $n = mb_strtolower((string) $empresa->nombre);

        return str_contains($n, 'prevention world')
            || str_contains($n, 'preventon world')
            || str_contains($n, 'preventios world');
    }

    /** Obtiene la empresa activa: sesión o usuario. */
    public static function empresaActiva(): ?Empresa
    {
        if (self::$empresaActivaCacheLoaded) {
            return self::$empresaActivaCache;
        }

        self::$empresaActivaCacheLoaded = true;

        $id = session(self::SESSION_KEY);
        if ($id !== null) {
            $empresa = Empresa::find($id);
            if ($empresa && self::usuarioPuedeAcceder($empresa)) {
                return self::$empresaActivaCache = $empresa;
            }
            session()->forget(self::SESSION_KEY);
        }
        $user = auth()->user();
        if ($user?->empresa_id) {
            return self::$empresaActivaCache = $user->empresa;
        }

        return self::$empresaActivaCache = null;
    }

    /** Establece la empresa activa en sesión (Entrar a empresa). */
    public static function entrarEmpresa(Empresa $empresa): void
    {
        abort_unless(self::usuarioPuedeAcceder($empresa), 403);
        session([self::SESSION_KEY => $empresa->id]);
        self::clearRequestCache();
    }

    /** Limpia la empresa activa de sesión. */
    public static function salirEmpresa(): void
    {
        session()->forget(self::SESSION_KEY);
        self::clearRequestCache();
    }

    private static function clearRequestCache(): void
    {
        self::$empresaActivaCache = null;
        self::$empresaActivaCacheLoaded = false;
    }

    public static function usuarioPuedeAcceder(?Empresa $empresa): bool
    {
        if (! $empresa) {
            return false;
        }

        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (! $user->empresa_id) {
            return true;
        }

        return (int) $user->empresa_id === (int) $empresa->id;
    }

    /** Prefijo para códigos (ej: APW, BVC). Por defecto APW si no hay. */
    public static function prefijo(): string
    {
        $empresa = self::empresaActiva();
        if ($empresa) {
            if (!empty($empresa->prefijo)) {
                return strtoupper(preg_replace('/[^A-Z0-9]/', '', $empresa->prefijo)) ?: 'EMP';
            }
            return self::codigoEmpresaDesdeNombre((string) $empresa->nombre);
        }
        return 'EMP';
    }

    /** ID de empresa activa para filtros. */
    public static function empresaId(): ?int
    {
        $empresa = self::empresaActiva();
        return $empresa?->id;
    }

    /** ID de empresa activa unificado (contexto o usuario). */
    public static function resolveId(): ?int
    {
        return self::empresaId() ?: auth()->user()?->empresa_id;
    }

    /** Color primario de la empresa activa (para tema). */
    public static function colorPrimario(): ?string
    {
        $empresa = self::empresaActiva();
        return $empresa?->color_primario;
    }

    private static function codigoEmpresaDesdeNombre(string $nombre): string
    {
        $clean = preg_replace('/[^A-Za-z0-9\s]/', ' ', $nombre) ?? '';
        $parts = preg_split('/\s+/', trim($clean)) ?: [];
        $parts = array_values(array_filter($parts, fn ($p) => $p !== ''));
        if (empty($parts)) {
            return 'EMP';
        }

        $maxWords = count($parts) > 3 ? 3 : count($parts);
        $initials = '';
        for ($i = 0; $i < $maxWords; $i++) {
            $initials .= strtoupper(substr($parts[$i], 0, 1));
        }

        $lastWord = (string) end($parts);
        $lastChar = strtoupper(substr($lastWord, -1));
        $code = strtoupper(preg_replace('/[^A-Z0-9]/', '', $initials . $lastChar));

        return $code !== '' ? $code : 'EMP';
    }
}
