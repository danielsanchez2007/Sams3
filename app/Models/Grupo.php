<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'name',
        'description',
        'leader_id',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'grupo_members');
    }
}
