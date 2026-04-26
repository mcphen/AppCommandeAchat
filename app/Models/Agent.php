<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Agent extends Model
{
    protected $fillable = [
        'user_id', 'boutique_id', 'matricule',
        'nom', 'prenom', 'telephone',
        'date_adhesion', 'is_active', 'photo_path',
    ];

    protected $casts = [
        'date_adhesion' => 'date',
        'is_active'     => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function boutique(): BelongsTo
    {
        return $this->belongsTo(Boutique::class);
    }

    public function compte(): HasOne
    {
        return $this->hasOne(CompteEpargne::class);
    }

    public function prets(): HasMany
    {
        return $this->hasMany(Pret::class)->latest();
    }

    public function nomComplet(): string
    {
        return "{$this->prenom} {$this->nom}";
    }

    public function pretActif(): ?Pret
    {
        return $this->prets()->where('statut', 'decaisse')->first();
    }
}
