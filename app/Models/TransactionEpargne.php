<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionEpargne extends Model
{
    protected $table = 'transactions_epargne';

    protected $fillable = [
        'compte_epargne_id', 'type', 'montant',
        'mode_reglement_id', 'reference', 'notes',
        'transaction_date', 'recorded_by',
    ];

    protected $casts = [
        'montant'          => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function compteEpargne(): BelongsTo
    {
        return $this->belongsTo(CompteEpargne::class);
    }

    public function modeReglement(): BelongsTo
    {
        return $this->belongsTo(ModeReglement::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function typeLabel(): string
    {
        return $this->type === 'depot' ? 'Dépôt' : 'Retrait';
    }

    public function typeColor(): string
    {
        return $this->type === 'depot' ? 'emerald' : 'orange';
    }
}
