<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Espacio extends Model
{
    protected $fillable = [
        'empresa_id',
        'sede_id',
        'nombre',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }
}
