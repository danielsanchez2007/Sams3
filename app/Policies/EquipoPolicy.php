<?php

namespace App\Policies;

use App\Models\Equipo;
use App\Models\EquipoBaja;
use App\Models\User;
use App\Services\EmpresaContext;
use App\Services\EmpresaModuleAuthorization;

class EquipoPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->authz($user)->canViewModule('equipos');
    }

    public function view(User $user, Equipo $equipo): bool
    {
        return $this->authz($user)->canViewModule('equipos') && $this->inTenant($equipo);
    }

    public function create(User $user): bool
    {
        return $this->authz($user)->canEditModule('equipos');
    }

    public function update(User $user, Equipo $equipo): bool
    {
        return $this->authz($user)->canEditModule('equipos') && $this->inTenant($equipo);
    }

    public function delete(User $user, Equipo $equipo): bool
    {
        return $this->update($user, $equipo);
    }

    public function darDeBaja(User $user, Equipo $equipo): bool
    {
        return $this->authz($user)->canEditModule('equipos_baja', 'equipos') && $this->inTenant($equipo);
    }

    public function viewBajas(User $user): bool
    {
        return $this->authz($user)->canViewModule('equipos_baja', 'equipos');
    }

    public function updateBaja(User $user, EquipoBaja $baja): bool
    {
        $equipo = $baja->equipo;
        if (!$equipo) {
            return false;
        }

        return $this->darDeBaja($user, $equipo);
    }

    public function traspasar(User $user, Equipo $equipo, string $destino = 'inventario'): bool
    {
        if (!$this->inTenant($equipo)) {
            return false;
        }

        $authz = $this->authz($user);

        return match ($destino) {
            'baja' => $authz->canEditModule('equipos_baja', 'equipos'),
            'auditoria' => $authz->canEditModule('auditoria', 'equipos'),
            'didactico' => $authz->canEditModule('material_didactico', 'equipos'),
            default => $authz->canEditModule('equipos'),
        };
    }

    public function viewAuditoria(User $user): bool
    {
        return $this->authz($user)->canViewModule('auditoria', 'equipos');
    }

    public function viewMaterial(User $user): bool
    {
        return $this->authz($user)->canViewModule('material_didactico', 'equipos');
    }

    private function authz(User $user): EmpresaModuleAuthorization
    {
        return new EmpresaModuleAuthorization($user);
    }

    private function inTenant(Equipo $equipo): bool
    {
        $activa = EmpresaContext::empresaId() ?? auth()->user()?->empresa_id;
        if (! $activa) {
            return false;
        }

        return $equipo->empresa_id === null || (int) $equipo->empresa_id === (int) $activa;
    }
}
