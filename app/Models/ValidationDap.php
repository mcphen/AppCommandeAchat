<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidationDap extends Model
{
    protected $table = 'validations_dap';

    protected $fillable = [
        'dap_id', 'niveau_validation_id', 'validateur_id',
        'statut', 'commentaire', 'validated_at',
    ];

    protected $casts = ['validated_at' => 'datetime'];

    const STATUT_APPROUVE = 'approuve';
    const STATUT_REJETE   = 'rejete';

    public function dap(): BelongsTo
    {
        return $this->belongsTo(DemandeAutorisationPaiement::class, 'dap_id');
    }

    public function niveauValidation(): BelongsTo
    {
        return $this->belongsTo(NiveauValidation::class);
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validateur_id');
    }

    public function isApprouve(): bool
    {
        return $this->statut === self::STATUT_APPROUVE;
    }
}
