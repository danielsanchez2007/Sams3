<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodigoReutilizable extends Model
{
    use HasFactory;

    protected $table = 'codigos_reutilizables';

    protected $fillable = [
        'codigo',
        'estado',
        'reservado_por',
        'reservado_en',
        'usado_en',
    ];

    protected $casts = [
        'reservado_en' => 'datetime',
        'usado_en' => 'datetime',
    ];
}
