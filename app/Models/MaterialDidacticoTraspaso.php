<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class MaterialDidacticoTraspaso extends Model
{
    use HasFactory;

    protected $table = 'material_didactico_traspasos';

    protected $fillable = [
        'equipo_id',
        'codigo_md',
        'codigo_anterior',
        'motivo',
        'traspasado_por',
        'traspasado_en',
    ];

    protected $casts = [
        'traspasado_en' => 'datetime',
    ];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'traspasado_por');
    }
}
