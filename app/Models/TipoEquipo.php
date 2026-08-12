<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoEquipo extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'nombre',
        'alias',
        'descripcion',
        'activo'
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    protected $casts = [
        'activo' => 'boolean'
    ];

    public function clases()
    {
        return $this->hasMany(ClaseEquipo::class);
    }

    public function equipos()
    {
        return $this->hasMany(Equipo::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
