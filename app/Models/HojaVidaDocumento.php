<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ClaseEquipo;
use App\Models\Equipo;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Models\HojaVidaPlantilla;

class HojaVidaDocumento extends Model
{
    protected $table = 'hoja_vida_documentos';

    protected $fillable = [
        'equipo_id',
        'tipo_equipo_id',
        'clase_equipo_id',
        'plantilla_id',
        'edited_html',
        'form_data',
        'selected_equipo_imagen_ids',
        'signature_user_id',
        'excel_path',
        'pdf_path',
        'creado_por',
        'actualizado_por',
    ];

    protected $casts = [
        'form_data' => 'array',
        'selected_equipo_imagen_ids' => 'array',
    ];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function tipoEquipo()
    {
        return $this->belongsTo(TipoEquipo::class);
    }

    public function claseEquipo()
    {
        return $this->belongsTo(ClaseEquipo::class, 'clase_equipo_id');
    }

    public function plantilla()
    {
        return $this->belongsTo(HojaVidaPlantilla::class, 'plantilla_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function actualizador()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }
}
