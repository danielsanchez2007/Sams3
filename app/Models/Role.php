<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'active',
        'activo',
    ];

    protected $hidden = [
        'permissions',
    ];

    protected $casts = [
        'active' => 'boolean',
        'activo' => 'boolean',
        'permissions' => 'array',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
