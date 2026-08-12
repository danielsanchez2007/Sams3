<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ClaseEquipo;
use App\Models\TipoEquipo;
use App\Models\User;

class HojaVidaPlantilla extends Model
{
    protected $table = 'hoja_vida_plantillas';

    protected $fillable = [
        'tipo_equipo_id',
        'clase_equipo_id',
        'plantilla_excel_path',
        'creado_por',
    ];

    public function claseEquipo()
    {
        return $this->belongsTo(ClaseEquipo::class, 'clase_equipo_id');
    }

    public function tipoEquipo()
    {
        return $this->belongsTo(TipoEquipo::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}
