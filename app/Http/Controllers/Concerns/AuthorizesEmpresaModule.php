<?php

namespace App\Http\Controllers\Concerns;

use App\Services\EmpresaContext;
use App\Services\EmpresaModuleAuthorization;

trait AuthorizesEmpresaModule
{
    protected function moduleAuthz(): EmpresaModuleAuthorization
    {
        return new EmpresaModuleAuthorization();
    }

    protected function assertCanViewModule(string $module, ?string $fallbackModule = null): void
    {
        $this->moduleAuthz()->assertCanViewModule($module, $fallbackModule);
    }

    protected function assertCanEditModule(string $module, ?string $fallbackModule = null): void
    {
        $this->moduleAuthz()->assertCanEditModule($module, $fallbackModule);
    }

    protected function resolveTenantEmpresaId(): ?int
    {
        $id = EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;

        return $id ? (int) $id : null;
    }

    protected function assertTenantEmpresaId(): int
    {
        $id = $this->resolveTenantEmpresaId();
        if (!$id) {
            abort(403, 'Debes seleccionar una empresa activa.');
        }

        return $id;
    }
}
