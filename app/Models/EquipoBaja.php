<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Equipo;
use App\Models\EquipoInspeccion;
use App\Models\User;

class EquipoBaja extends Model
{
    protected $table = 'equipos_baja';

    protected $fillable = [
        'equipo_id',
        'equipo_inspeccion_id',
        'codigo_db',
        'codigo_in',
        'creado_por',
        'fecha_baja',
        'motivo_baja',
        'observaciones_baja',
        'equipo_snapshot',
        'form_data',
        'plantilla_excel_path',
        'pdf_path',
    ];

    protected $casts = [
        'fecha_baja' => 'date',
        'equipo_snapshot' => 'array',
        'form_data' => 'array',
    ];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function inspeccion()
    {
        return $this->belongsTo(EquipoInspeccion::class, 'equipo_inspeccion_id');
    }
}
