<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialMantenimiento extends Model
{
    use HasFactory;

    protected $fillable = [
        'equipo_id',
        'tipo_mantenimiento',
        'descripcion',
        'fecha_mantenimiento',
        'tecnico_id',
        'costo',
        'repuestos',
        'observaciones',
        'estado_equipo'
    ];

    protected $casts = [
        'fecha_mantenimiento' => 'date',
        'costo' => 'decimal:2'
    ];

    const TIPOS = [
        'preventivo' => 'Mantenimiento Preventivo',
        'correctivo' => 'Mantenimiento Correctivo',
        'calibracion' => 'Calibración',
        'limpieza' => 'Limpieza',
        'actualizacion' => 'Actualización',
        'reparacion' => 'Reparación'
    ];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function tecnico()
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    public function getTipoMantenimientoLabelAttribute()
    {
        return self::TIPOS[$this->tipo_mantenimiento] ?? $this->tipo_mantenimiento;
    }
}
