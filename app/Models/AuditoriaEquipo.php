<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditoriaEquipo extends Model
{
    use HasFactory;

    protected $fillable = [
        'equipo_id',
        'auditor_id',
        'fecha_auditoria',
        'estado_fisico',
        'estado_funcional',
        'ubicacion_verificada',
        'responsable_verificado',
        'observaciones',
        'recomendaciones',
        'cumple_normas',
        'puntuacion'
    ];

    protected $casts = [
        'fecha_auditoria' => 'date',
        'cumple_normas' => 'boolean',
        'puntuacion' => 'integer'
    ];

    const ESTADOS_FISICOS = [
        'excelente' => 'Excelente',
        'bueno' => 'Bueno',
        'regular' => 'Regular',
        'malo' => 'Malo',
        'muy_malo' => 'Muy Malo'
    ];

    const ESTADOS_FUNCIONALES = [
        'optimo' => 'Óptimo',
        'funcional' => 'Funcional',
        'parcial' => 'Parcialmente Funcional',
        'no_funcional' => 'No Funcional',
        'danado' => 'Dañado'
    ];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function auditor()
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    public function getEstadoFisicoLabelAttribute()
    {
        return self::ESTADOS_FISICOS[$this->estado_fisico] ?? $this->estado_fisico;
    }

    public function getEstadoFuncionalLabelAttribute()
    {
        return self::ESTADOS_FUNCIONALES[$this->estado_funcional] ?? $this->estado_funcional;
    }
}
