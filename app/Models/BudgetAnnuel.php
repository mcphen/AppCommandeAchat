<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetAnnuel extends Model
{
    protected $table = 'budgets_annuels';

    protected $fillable = [
        'entreprise_id', 'annee', 'libelle',
        'montant_total', 'montant_consomme', 'is_active',
    ];

    protected $casts = [
        'montant_total'    => 'decimal:2',
        'montant_consomme' => 'decimal:2',
        'is_active'        => 'boolean',
    ];

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function daps(): HasMany
    {
        return $this->hasMany(DemandeAutorisationPaiement::class, 'budget_annuel_id');
    }

    public function getMontantDisponibleAttribute(): float
    {
        return (float) $this->montant_total - (float) $this->montant_consomme;
    }
}
