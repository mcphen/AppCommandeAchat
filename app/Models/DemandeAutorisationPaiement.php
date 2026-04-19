<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DemandeAutorisationPaiement extends Model
{
    protected $table = 'demandes_autorisation_paiement';

    protected $fillable = [
        'reference', 'expression_besoin_id', 'budget_annuel_id',
        'created_by', 'statut', 'notes',
    ];

    const STATUT_EN_COURS = 'en_cours';
    const STATUT_VALIDEE  = 'validee';
    const STATUT_REJETEE  = 'rejetee';
    const STATUT_PAYEE    = 'payee';

    public function expressionBesoin(): BelongsTo
    {
        return $this->belongsTo(ExpressionBesoin::class);
    }

    public function budgetAnnuel(): BelongsTo
    {
        return $this->belongsTo(BudgetAnnuel::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validations(): HasMany
    {
        return $this->hasMany(ValidationDap::class, 'dap_id')->orderBy('validated_at');
    }

    public function paiement(): HasOne
    {
        return $this->hasOne(Paiement::class, 'dap_id');
    }

    public function isEnCours(): bool
    {
        return $this->statut === self::STATUT_EN_COURS;
    }

    public function isValidee(): bool
    {
        return $this->statut === self::STATUT_VALIDEE;
    }

    public function isPayee(): bool
    {
        return $this->statut === self::STATUT_PAYEE;
    }

    public function getMontantAttribute(): float
    {
        return (float) $this->expressionBesoin?->montant;
    }
}
