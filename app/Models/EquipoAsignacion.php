<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipoAsignacion extends Model
{
    protected $table = 'equipo_asignaciones';

    protected $fillable = [
        'user_id',
        'equipo_id',
        'asignado_at',
    ];

    protected $casts = [
        'asignado_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }
}
