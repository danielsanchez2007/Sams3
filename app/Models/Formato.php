<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Formato extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo',
        'contenido',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean'
    ];

    const TIPOS = [
        'hoja_vida' => 'Hoja de Vida',
        'inspeccion' => 'Inspección',
        'exportacion' => 'Exportación'
    ];

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function getTipoLabelAttribute()
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }
}
