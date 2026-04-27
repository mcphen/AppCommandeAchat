<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessionArbitrage extends Model
{
    protected $table = 'sessions_arbitrage';

    protected $fillable = [
        'reference', 'comite_arbitrage_id', 'titre', 'description',
        'tresorerie_disponible', 'bloquer_depassement', 'statut',
        'date_ouverture', 'date_cloture', 'created_by', 'finalise_par',
    ];

    protected $casts = [
        'tresorerie_disponible' => 'float',
        'bloquer_depassement'   => 'boolean',
        'date_ouverture'        => 'date',
        'date_cloture'          => 'date',
    ];

    const STATUT_BROUILLON = 'brouillon';
    const STATUT_EN_VOTE   = 'en_vote';
    const STATUT_CLOTUREE  = 'cloturee';
    const STATUT_ANNULEE   = 'annulee';

    public function comite(): BelongsTo
    {
        return $this->belongsTo(ComiteArbitrage::class, 'comite_arbitrage_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function finaliseePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalise_par');
    }

    public function sessionDaps(): HasMany
    {
        return $this->hasMany(SessionArbitrageDap::class, 'session_arbitrage_id')->orderBy('ordre_final')->orderBy('score_moyen');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(VoteArbitrage::class, 'session_arbitrage_id');
    }

    public function quorumAtteint(): bool
    {
        $membresActifs = $this->comite->membres()->where('is_active', true)->count();
        $quorumRequis  = max(1, (int) floor($membresActifs / 2) + 1);
        $votantsUniques = $this->votes()->distinct('user_id')->count('user_id');
        return $votantsUniques >= $quorumRequis;
    }

    public function getNbVotantsAttribute(): int
    {
        return $this->votes()->distinct('user_id')->count('user_id');
    }

    public function getNbMembresActifsAttribute(): int
    {
        return $this->comite->membres()->where('is_active', true)->count();
    }

    public function getQuorumRequisAttribute(): int
    {
        return max(1, (int) floor($this->nb_membres_actifs / 2) + 1);
    }

    public function calculerScores(): void
    {
        foreach ($this->sessionDaps as $item) {
            $avg = VoteArbitrage::where('session_arbitrage_id', $this->id)
                ->where('dap_id', $item->dap_id)
                ->avg('rang');
            $item->update(['score_moyen' => $avg]);
        }
    }

    public static function genererReference(): string
    {
        $annee = date('Y');
        $dernier = static::whereYear('created_at', $annee)->count() + 1;
        return 'ARB-' . $annee . '-' . str_pad($dernier, 3, '0', STR_PAD_LEFT);
    }
}
