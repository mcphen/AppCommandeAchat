<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password',
        'role_id', 'entreprise_id', 'fonction', 'signature_path',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function expressionsBesoin(): HasMany
    {
        return $this->hasMany(ExpressionBesoin::class);
    }

    public function validateur(): HasOne
    {
        return $this->hasOne(Validateur::class);
    }

    public function isAdmin(): bool
    {
        return $this->role?->slug === 'admin';
    }

    public function isEmploye(): bool
    {
        return $this->role?->slug === 'employe';
    }

    public function isValidateur(): bool
    {
        return $this->role?->slug === 'validateur';
    }

    public function isCompta(): bool
    {
        return $this->validateur?->niveauValidation?->slug === 'compta';
    }

    public function isDf(): bool
    {
        return $this->validateur?->niveauValidation?->slug === 'df';
    }

    public function canValidate(): bool
    {
        return $this->isAdmin() || $this->isValidateur();
    }

    public function getNiveauValidationAttribute(): ?NiveauValidation
    {
        return $this->validateur?->niveauValidation;
    }

    public function delegationsDonnees(): HasMany
    {
        return $this->hasMany(Delegation::class, 'delegant_id');
    }

    public function delegationsRecues(): HasMany
    {
        return $this->hasMany(Delegation::class, 'delegataire_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(Audit::class);
    }

    public function escalades(): HasMany
    {
        return $this->hasMany(Escalade::class);
    }
}
