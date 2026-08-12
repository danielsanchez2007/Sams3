<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class EquipoAuditoriaTraspaso extends Model
{
    use HasFactory;

    protected $table = 'equipos_auditoria_traspasos';

    protected $fillable = [
        'equipo_id',
        'codigo_aud',
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
