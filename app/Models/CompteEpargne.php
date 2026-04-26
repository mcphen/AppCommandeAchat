<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompteEpargne extends Model
{
    protected $table = 'comptes_epargne';

    protected $fillable = [
        'agent_id', 'solde_epargne', 'solde_bloque', 'opened_at', 'is_active',
    ];

    protected $casts = [
        'solde_epargne' => 'decimal:2',
        'solde_bloque'  => 'decimal:2',
        'opened_at'     => 'date',
        'is_active'     => 'boolean',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(TransactionEpargne::class)->with(['modeReglement', 'recorder'])->latest();
    }

    public function soldeDispo(): float
    {
        return max(0, (float) $this->solde_epargne - (float) $this->solde_bloque);
    }

    public function plafondPret(): float
    {
        $multiplicateur = (int) \App\Models\AppSetting::get('plafond_multiplicateur_pret', 3);
        return $this->soldeDispo() * $multiplicateur;
    }
}
