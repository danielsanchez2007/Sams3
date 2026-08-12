<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'codigo',
        'name',
        'last_name',
        'gender',
        'gender_other',
        'email',
        'password',
        'document_type',
        'document_number',
        'phone',
        'address',
        'has_corporate_email',
        'corporate_email',
        'has_corporate_phone',
        'corporate_phone',
        'birth_date',
        'photo',
        'signature',
        'role_id',
        'cargo_id',
        'grupo_id',
        'empresa_id',
        'active',
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'has_corporate_email' => 'boolean',
            'has_corporate_phone' => 'boolean',
            'birth_date' => 'date',
            'active' => 'boolean',
            'role_id' => 'integer',
            'cargo_id' => 'integer',
            'grupo_id' => 'integer',
            'empresa_id' => 'integer',
            'must_change_password' => 'boolean',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function cargo()
    {
        return $this->belongsTo(Cargo::class);
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function equipoAsignaciones()
    {
        return $this->hasMany(EquipoAsignacion::class);
    }

    public function equiposAsignados()
    {
        return $this->hasManyThrough(Equipo::class, EquipoAsignacion::class, 'user_id', 'id', 'id', 'equipo_id');
    }

    public function solicitudesAsignacionRecibidas()
    {
        return $this->hasMany(EquipoAsignacionSolicitud::class, 'to_user_id');
    }

    public function prestamosTemporalesRecibidos()
    {
        return $this->hasMany(PrestamoTemporal::class, 'to_user_id');
    }

    /**
     * Indica si el perfil está completo (requerido para usar el sistema).
     */
    public function isProfileComplete(): bool
    {
        return !empty($this->photo)
            && !empty($this->signature)
            && !empty($this->document_type)
            && !empty($this->document_number);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->buildMediaUrl($this->photo);
    }

    public function getSignatureUrlAttribute(): ?string
    {
        return $this->buildMediaUrl($this->signature);
    }

    private function buildMediaUrl(?string $path): ?string
    {
        $clean = trim((string) $path);
        if ($clean === '') {
            return null;
        }
        if (str_starts_with($clean, 'http://') || str_starts_with($clean, 'https://')) {
            return $clean;
        }
        $clean = preg_replace('#^/?public/#', '', $clean);
        $clean = preg_replace('#^/?storage/#', '', (string) $clean);
        return url('storage/' . ltrim((string) $clean, '/'));
    }
}
