<?php

namespace App\Policies;

use App\Models\User;
use App\Services\EmpresaContext;

class UserPolicy
{
    private function canViewUsersModule(User $actor): bool
    {
        if (!$actor->exists) {
            return false;
        }

        $isGlobalAdmin = !$actor->empresa_id;
        $roleName = strtolower(trim((string) ($actor->role?->name ?? '')));
        $isEmpresaAdminByRole = $actor->empresa_id && (
            str_contains($roleName, 'admin') || str_contains($roleName, 'administrador')
        );
        $rolePerms = $actor->role?->permissions;
        $isEmpresaAdminByPerm = $actor->empresa_id && is_array($rolePerms) && in_array('users', $rolePerms, true);

        $empresa = EmpresaContext::empresaActiva();
        if (!$empresa || !is_array($empresa->modulos)) {
            return $isGlobalAdmin || $isEmpresaAdminByRole || $isEmpresaAdminByPerm;
        }

        $val = $empresa->modulos['users'] ?? $empresa->modulos['gestion_principal'] ?? 'none';
        if (!in_array($val, ['none', 'view', 'edit'], true)) {
            $val = 'none';
        }
        $moduloPermiteVista = in_array($val, ['view', 'edit'], true);

        if ($isGlobalAdmin) {
            return $moduloPermiteVista;
        }

        return $moduloPermiteVista && ($isEmpresaAdminByRole || $isEmpresaAdminByPerm);
    }

    private function canEditUsersModule(User $actor): bool
    {
        if (!$this->canViewUsersModule($actor)) {
            return false;
        }

        $empresa = EmpresaContext::empresaActiva();
        if (!$empresa || !is_array($empresa->modulos)) {
            return true;
        }

        $val = $empresa->modulos['users'] ?? $empresa->modulos['gestion_principal'] ?? 'none';

        return $val === 'edit';
    }

    private function userInCurrentEmpresa(User $actor, User $target): bool
    {
        if (!$actor->empresa_id) {
            return true;
        }

        return (int) $target->empresa_id === (int) $actor->empresa_id;
    }

    public function viewAny(User $actor): bool
    {
        return $this->canViewUsersModule($actor);
    }

    public function view(User $actor, User $target): bool
    {
        return $this->userInCurrentEmpresa($actor, $target);
    }

    public function create(User $actor): bool
    {
        return $this->canEditUsersModule($actor);
    }

    public function update(User $actor, User $target): bool
    {
        return $this->canEditUsersModule($actor) && $this->userInCurrentEmpresa($actor, $target);
    }

    public function delete(User $actor, User $target): bool
    {
        return $this->update($actor, $target) && (int) $actor->id !== (int) $target->id;
    }
}
