<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrestamoTemporalItem extends Model
{
    protected $table = 'prestamo_temporal_items';

    protected $fillable = [
        'prestamo_id',
        'equipo_id',
        'revision_estado',
        'revision_novedad',
        'revision_by',
        'revision_at',
    ];

    protected $casts = [
        'revision_at' => 'datetime',
    ];

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(PrestamoTemporal::class, 'prestamo_id');
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_id');
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revision_by');
    }
}

