<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'name',
        'description',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
