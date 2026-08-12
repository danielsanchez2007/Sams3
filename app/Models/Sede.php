<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sede extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'nombre',
        'pais',
        'departamento',
        'municipio',
        'ciudad',
        'direccion',
        'google_maps_url',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean'
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function bodegas()
    {
        return $this->hasMany(Bodega::class);
    }

    public function oficinas()
    {
        return $this->hasMany(Oficina::class);
    }

    public function espacios()
    {
        return $this->hasMany(Espacio::class);
    }

    public function equipos()
    {
        return $this->hasMany(Equipo::class);
    }
}
