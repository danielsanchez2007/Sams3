<?php

namespace App\Services;

use App\Models\User;

/**
 * Autorización por módulo de empresa (SRP: una sola fuente de verdad).
 */
class EmpresaModuleAuthorization
{
    public function __construct(
        private ?User $user = null,
    ) {
        $this->user = $user ?? auth()->user();
    }

    public function isGlobalAdmin(): bool
    {
        return $this->user && !$this->user->empresa_id;
    }

    public function resolveEmpresaActivaId(): ?int
    {
        return EmpresaContext::resolveId();
    }

    /**
     * @param  'none'|'view'|'edit'  $defaultWhenUnset
     * @return 'none'|'view'|'edit'
     */
    public function moduleLevel(string $module, ?string $fallbackModule = null, string $defaultWhenUnset = 'edit'): string
    {
        $empresaActivaId = $this->resolveEmpresaActivaId();
        if (!$empresaActivaId) {
            return $this->isGlobalAdmin() ? 'edit' : 'none';
        }

        $empresa = EmpresaContext::empresaActiva();
        $modulos = is_array($empresa?->modulos) ? $empresa->modulos : [];
        $val = $modulos[$module] ?? ($fallbackModule ? ($modulos[$fallbackModule] ?? null) : null);

        if ($val === null) {
            $val = $defaultWhenUnset;
        }

        if (!in_array($val, ['none', 'view', 'edit'], true)) {
            return 'none';
        }

        return $val;
    }

    /**
     * @param  'none'|'view'|'edit'  $defaultWhenUnset
     */
    public function canViewModule(string $module, ?string $fallbackModule = null, string $defaultWhenUnset = 'edit'): bool
    {
        return in_array($this->moduleLevel($module, $fallbackModule, $defaultWhenUnset), ['view', 'edit'], true);
    }

    /**
     * @param  'none'|'view'|'edit'  $defaultWhenUnset
     */
    public function canEditModule(string $module, ?string $fallbackModule = null, string $defaultWhenUnset = 'edit'): bool
    {
        return $this->moduleLevel($module, $fallbackModule, $defaultWhenUnset) === 'edit';
    }

    public function assertTenantOwns(int $resourceEmpresaId): void
    {
        $activaId = $this->resolveEmpresaActivaId();
        if ($activaId && (int) $activaId !== (int) $resourceEmpresaId) {
            abort(403, 'No tienes permiso para acceder a recursos de otra empresa.');
        }
    }

    public function assertCanEditEmpresa(?int $empresaId = null): void
    {
        if ($this->isGlobalAdmin() && !EmpresaContext::empresaId()) {
            return;
        }

        if (!$this->canEditModule('empresa')) {
            abort(403, 'No tienes permiso para editar la empresa.');
        }

        if ($empresaId !== null) {
            $this->assertTenantOwns($empresaId);
        }
    }

    public function assertCanEditSede(int $sedeEmpresaId): void
    {
        $this->assertTenantOwns($sedeEmpresaId);

        if ($this->isGlobalAdmin() && !EmpresaContext::empresaId()) {
            return;
        }

        if (!$this->canEditModule('sede', 'empresa')) {
            abort(403, 'No tienes permiso para gestionar sedes.');
        }
    }

    public function assertCanEditBodega(int $bodegaEmpresaId): void
    {
        $this->assertTenantOwns($bodegaEmpresaId);

        if ($this->isGlobalAdmin() && !EmpresaContext::empresaId()) {
            return;
        }

        if (!$this->canEditModule('bodega', 'empresa')) {
            abort(403, 'No tienes permiso para gestionar bodegas.');
        }
    }
}
