<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExpressionBesoin extends Model
{
    protected $table = 'expressions_besoin';

    protected $fillable = [
        'reference', 'user_id', 'entreprise_id', 'objet',
        'description', 'montant', 'beneficiaire', 'justification',
        'statut', 'motif_rejet', 'rejected_at',
    ];

    protected $casts = [
        'montant'     => 'decimal:2',
        'rejected_at' => 'datetime',
    ];

    // Statuts possibles
    const STATUT_EN_ATTENTE = 'en_attente';
    const STATUT_VALIDEE    = 'validee';
    const STATUT_REJETEE    = 'rejetee';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ExpressionBesoinAttachment::class);
    }

    public function dap(): HasOne
    {
        return $this->hasOne(DemandeAutorisationPaiement::class);
    }

    public function isEnAttente(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE;
    }

    public function isValidee(): bool
    {
        return $this->statut === self::STATUT_VALIDEE;
    }

    public function isRejetee(): bool
    {
        return $this->statut === self::STATUT_REJETEE;
    }
}
