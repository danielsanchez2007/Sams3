<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Bodega;
use App\Models\ClaseEquipo;
use App\Models\Empresa;
use App\Models\EquipoImagen;
use App\Models\EquipoKitItem;
use App\Models\Espacio;
use App\Models\Fabricante;
use App\Models\Oficina;
use App\Models\Sede;
use App\Models\TipoEquipo;

class Equipo extends Model
{
    use HasFactory, SoftDeletes, BelongsToEmpresa;

    protected $fillable = [
        'nombre',
        'serial',
        'descripcion',
        'activo',
        'tipo_equipo_id',
        'clase_equipo_id',
        'codigo',
        'vida_util',
        'estado_item',
        'observacion',
        'fabricante_id',
        'empresa_id',
        'sede_id',
        'ubicacion_tipo',
        'va_a_bodega',
        'bodega_id',
        'oficina_id',
        'espacio_id',
        'fecha_fabricacion',
        'fecha_uso',
        'tipo_uso',
        'tipo_uso_otro',
        'es_kit',
        'kit_nombre',
        'kit_cantidad',
        'certificacion_descripcion',
        'especificaciones_tecnicas',
        'valor_equipo',
        'numero_factura',
        'fecha_compra',
        'lote',
        'tiene_resistencia',
        'resistencia_descripcion',
        'tiene_manual_fabricante',
        'tiene_certificacion_fabricante',
        'va_a_bodega',
    ];

    protected $casts = [
        'vida_util' => 'integer',
        'activo' => 'boolean',
        'fecha_fabricacion' => 'date',
        'fecha_uso' => 'date',
        'fecha_compra' => 'date',
        'es_kit' => 'boolean',
        'kit_cantidad' => 'integer',
        'valor_equipo' => 'decimal:2',
        'tiene_resistencia' => 'boolean',
        'tiene_manual_fabricante' => 'boolean',
        'tiene_certificacion_fabricante' => 'boolean',
        'va_a_bodega' => 'boolean'
    ];

    public function tipoEquipo()
    {
        return $this->belongsTo(TipoEquipo::class);
    }

    public function claseEquipo()
    {
        return $this->belongsTo(ClaseEquipo::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    public function bodega()
    {
        return $this->belongsTo(Bodega::class);
    }

    public function oficina()
    {
        return $this->belongsTo(Oficina::class);
    }

    public function espacio()
    {
        return $this->belongsTo(Espacio::class);
    }

    public function fabricante()
    {
        return $this->belongsTo(Fabricante::class);
    }

    public function imagenes()
    {
        return $this->hasMany(EquipoImagen::class);
    }

    public function kitItems()
    {
        return $this->hasMany(EquipoKitItem::class);
    }

    public function archivos()
    {
        return $this->hasMany(EquipoArchivo::class);
    }

    public function inspecciones()
    {
        return $this->hasMany(EquipoInspeccion::class);
    }

    public function asignacion()
    {
        return $this->hasOne(EquipoAsignacion::class);
    }

    public function userAsignado()
    {
        return $this->hasOneThrough(User::class, EquipoAsignacion::class, 'equipo_id', 'id', 'id', 'user_id');
    }

    public function prestamoTemporalItems()
    {
        return $this->hasMany(PrestamoTemporalItem::class, 'equipo_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Excluye equipos cuyo tipo (nombre o alias) es papelería — van por material didáctico u otros flujos.
     */
    public function scopeSinTipoPapeleria(Builder $query): Builder
    {
        return $query->where(function (Builder $w) {
            $w->whereNull('tipo_equipo_id')
                ->orWhereDoesntHave('tipoEquipo', function (Builder $t) {
                    $t->where(function (Builder $inner) {
                        $inner->whereRaw('LOWER(nombre) LIKE ?', ['%papeler%'])
                            ->orWhereRaw('LOWER(COALESCE(alias, \'\')) LIKE ?', ['%papeler%']);
                    });
                });
        });
    }
}
