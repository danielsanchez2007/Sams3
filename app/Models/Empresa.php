<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'prefijo',
        'color_primario',
        'default_role_id',
        'default_user_id',
        'pais',
        'departamento',
        'municipio',
        'ciudad',
        'nit',
        'direccion',
        'google_maps_url',
        'telefono',
        'email',
        'sitio_web',
        'logo',
        'logo_principal',
        'logo_secundario',
        'foto_empresa',
        'latitud',
        'longitud',
        'altitud',
        'color_secundario_1',
        'color_secundario_2',
        'color_extra_4',
        'color_extra_5',
        'activo',
    ];

    protected $hidden = [
        'modulos',
        'code_settings',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'modulos' => 'array',
        'code_settings' => 'array',
    ];

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /** Prevention World primero en listados. */
    public function scopeOrderMatrizFirst($query)
    {
        return $query->orderByRaw("CASE WHEN LOWER(nombre) LIKE '%prevention world%' OR LOWER(nombre) LIKE '%preventon world%' THEN 0 ELSE 1 END");
    }

    public function sedes()
    {
        return $this->hasMany(Sede::class);
    }

    public function bodegas()
    {
        return $this->hasMany(Bodega::class);
    }

    public function oficinas()
    {
        return $this->hasMany(Oficina::class);
    }

    public function espacios()
    {
        return $this->hasMany(Espacio::class);
    }

    public function equipos()
    {
        return $this->hasMany(Equipo::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function tiposEquipo()
    {
        return $this->hasMany(TipoEquipo::class);
    }
}
