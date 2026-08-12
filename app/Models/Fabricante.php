<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fabricante extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'contacto',
        'telefono',
        'email',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean'
    ];
}
