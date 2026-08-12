<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipoAsignacionSolicitud extends Model
{
    protected $table = 'equipo_asignacion_solicitudes';

    protected $fillable = [
        'created_by',
        'to_user_id',
        'tipo',
        'estado',
        'workflow_step',
        'html_formulario',
        'comentario_revision',
        'respondido_at',
    ];

    protected $casts = [
        'respondido_at' => 'datetime',
    ];

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function destinatario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(EquipoAsignacionSolicitudItem::class, 'solicitud_id');
    }
}

