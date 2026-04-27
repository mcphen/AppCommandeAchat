<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoteArbitrage extends Model
{
    protected $table = 'votes_arbitrage';

    protected $fillable = [
        'session_arbitrage_id', 'dap_id', 'user_id', 'rang', 'commentaire', 'voted_at',
    ];

    protected $casts = [
        'voted_at' => 'datetime',
        'rang'     => 'integer',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(SessionArbitrage::class, 'session_arbitrage_id');
    }

    public function dap(): BelongsTo
    {
        return $this->belongsTo(DemandeAutorisationPaiement::class, 'dap_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
