<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspeccionPlantilla extends Model
{
    protected $table = 'inspeccion_plantillas';

    protected $fillable = [
        'tipo_equipo_id',
        'clase_equipo_id',
        'plantilla_excel_path',
    ];

    public function tipoEquipo()
    {
        return $this->belongsTo(TipoEquipo::class);
    }

    public function claseEquipo()
    {
        return $this->belongsTo(ClaseEquipo::class);
    }
}
