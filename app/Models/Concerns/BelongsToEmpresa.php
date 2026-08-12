<?php

namespace App\Models\Concerns;

use App\Services\EmpresaContext;

trait BelongsToEmpresa
{
    protected static function bootBelongsToEmpresa(): void
    {
        static::creating(function ($model): void {
            if (!empty($model->empresa_id)) {
                return;
            }

            $empresaId = EmpresaContext::empresaId();
            if ($empresaId) {
                $model->empresa_id = $empresaId;
            }
        });

        if (app()->runningInConsole()) {
            return;
        }

        static::addGlobalScope('empresa', function ($query): void {
            $empresaId = EmpresaContext::empresaId();
            if ($empresaId) {
                $query->where($query->getModel()->getTable() . '.empresa_id', $empresaId);
            }
        });
    }
}
