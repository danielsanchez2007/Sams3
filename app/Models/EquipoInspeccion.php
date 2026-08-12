<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** @property-read \App\Models\EquipoBaja|null $equipoBaja */

class EquipoInspeccion extends Model
{
    protected $table = 'equipo_inspecciones';

    protected $fillable = [
        'equipo_id',
        'fecha_inspeccion',
        'validez_hasta',
        'edited_html',
        'pdf_path',
        'inspeccion_obligatoria',
        'user_id',
        'inspector_user_id',
        'selected_user_ids',
        'dado_de_baja',
        'equipo_baja_id',
    ];

    protected $casts = [
        'fecha_inspeccion' => 'date',
        'validez_hasta' => 'date',
        'inspeccion_obligatoria' => 'boolean',
        'dado_de_baja' => 'boolean',
        'selected_user_ids' => 'array',
    ];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_user_id');
    }

    public function equipoBaja()
    {
        return $this->belongsTo(EquipoBaja::class, 'equipo_baja_id');
    }
}
