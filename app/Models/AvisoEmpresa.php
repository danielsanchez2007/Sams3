<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;

class AvisoEmpresa extends Model
{
    use BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'tipo',
        'mensaje',
        'imagen_cumplimiento_path',
        'created_by',
    ];

    public function cumplido(): bool
    {
        return $this->tipo === 'ok' && !empty($this->imagen_cumplimiento_path);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeObservaciones($query)
    {
        return $query->where('tipo', 'observacion');
    }

    public function scopeOk($query)
    {
        return $query->where('tipo', 'ok');
    }
}
