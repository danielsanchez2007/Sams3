<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrestamoTemporal extends Model
{
    protected $table = 'prestamos_temporales';

    protected $fillable = [
        'created_by',
        'to_user_id',
        'fecha_salida',
        'fecha_fin',
        'estado',
        'html_formulario',
        'devuelto_at',
        'finalizado_at',
    ];

    protected $casts = [
        'fecha_salida' => 'date',
        'fecha_fin' => 'date',
        'devuelto_at' => 'datetime',
        'finalizado_at' => 'datetime',
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
        return $this->hasMany(PrestamoTemporalItem::class, 'prestamo_id');
    }
}

