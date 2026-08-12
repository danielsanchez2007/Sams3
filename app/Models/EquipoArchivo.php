<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipoArchivo extends Model
{
    use HasFactory;

    protected $table = 'equipo_archivos';

    protected $fillable = [
        'equipo_id',
        'nombre',
        'path',
        'mime',
        'size',
        'original_name',
    ];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }
}
