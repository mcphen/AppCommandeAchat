<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionArbitrageDap extends Model
{
    protected $table = 'session_arbitrage_dap';

    protected $fillable = [
        'session_arbitrage_id', 'dap_id', 'score_moyen', 'ordre_final', 'statut',
    ];

    protected $casts = [
        'score_moyen' => 'float',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(SessionArbitrage::class, 'session_arbitrage_id');
    }

    public function dap(): BelongsTo
    {
        return $this->belongsTo(DemandeAutorisationPaiement::class, 'dap_id');
    }

    public function isSelectionne(): bool
    {
        return $this->statut === 'selectionne';
    }
}
