<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pret extends Model
{
    protected $fillable = [
        'agent_id', 'compte_epargne_id',
        'montant_demande', 'montant_accorde', 'motif', 'statut',
        'current_level_order', 'mode_reglement_id',
        'submitted_at', 'decaisse_at', 'solde_at', 'recorded_by',
    ];

    protected $casts = [
        'montant_demande'     => 'decimal:2',
        'montant_accorde'     => 'decimal:2',
        'current_level_order' => 'integer',
        'submitted_at'        => 'datetime',
        'decaisse_at'         => 'datetime',
        'solde_at'            => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function compteEpargne(): BelongsTo
    {
        return $this->belongsTo(CompteEpargne::class, 'compte_epargne_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function modeReglement(): BelongsTo
    {
        return $this->belongsTo(ModeReglement::class);
    }

    public function validationLogs(): HasMany
    {
        return $this->hasMany(PretValidationLog::class)->with(['validationLevel', 'user'])->latest();
    }

    public function remboursements(): HasMany
    {
        return $this->hasMany(RemboursementPret::class)->with(['modeReglement', 'recorder'])->latest();
    }

    // ── Statuts ───────────────────────────────────────────────────────────────
    public function isDraft(): bool    { return $this->statut === 'draft'; }
    public function isPending(): bool  { return $this->statut === 'pending'; }
    public function isApproved(): bool { return $this->statut === 'approved'; }
    public function isRejected(): bool { return $this->statut === 'rejected'; }
    public function isDecaisse(): bool { return $this->statut === 'decaisse'; }
    public function isSolde(): bool    { return $this->statut === 'solde'; }

    public function statutLabel(): string
    {
        return match ($this->statut) {
            'draft'    => 'Brouillon',
            'pending'  => 'En validation',
            'approved' => 'Approuvé',
            'rejected' => 'Rejeté',
            'decaisse' => 'Décaissé',
            'solde'    => 'Soldé',
            default    => $this->statut,
        };
    }

    public function statutColor(): string
    {
        return match ($this->statut) {
            'draft'    => 'gray',
            'pending'  => 'yellow',
            'approved' => 'blue',
            'rejected' => 'red',
            'decaisse' => 'orange',
            'solde'    => 'emerald',
            default    => 'gray',
        };
    }

    // ── Calculs ───────────────────────────────────────────────────────────────
    public function capitalRembourse(): float
    {
        return (float) $this->remboursements()->sum('montant');
    }

    public function resteARembourser(): float
    {
        return max(0, (float) ($this->montant_accorde ?? $this->montant_demande) - $this->capitalRembourse());
    }

    public function progressRemboursement(): int
    {
        $total = (float) ($this->montant_accorde ?? $this->montant_demande);
        if (! $total) return 0;
        return min(100, (int) round(($this->capitalRembourse() / $total) * 100));
    }

    public function currentLevel(): ?ValidationLevel
    {
        if (! $this->current_level_order) return null;
        return ValidationLevel::where('order', $this->current_level_order)->first();
    }

    public function canBeSubmitted(): bool
    {
        return $this->statut === 'draft';
    }

    public function canBeDecaisse(): bool
    {
        return $this->statut === 'approved';
    }

    public function canBeRembourse(): bool
    {
        return $this->statut === 'decaisse';
    }
}
