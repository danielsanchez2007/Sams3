<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipoKitItem extends Model
{
    use HasFactory;

    protected $table = 'equipo_kit_items';

    protected $fillable = [
        'equipo_id',
        'nombre',
        'descripcion',
        'foto_path',
    ];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }
}
