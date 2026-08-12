<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bodega extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'sede_id',
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

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    public function equipos()
    {
        return $this->hasMany(Equipo::class);
    }
}
