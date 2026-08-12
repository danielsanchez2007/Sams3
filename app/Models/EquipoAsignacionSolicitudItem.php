<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipoAsignacionSolicitudItem extends Model
{
    protected $table = 'equipo_asignacion_solicitud_items';

    protected $fillable = [
        'solicitud_id',
        'equipo_id',
        'from_user_id',
    ];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(EquipoAsignacionSolicitud::class, 'solicitud_id');
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_id');
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }
}

