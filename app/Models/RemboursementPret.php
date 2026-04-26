<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemboursementPret extends Model
{
    protected $table = 'remboursements_pret';

    protected $fillable = [
        'pret_id', 'montant', 'mode_reglement_id',
        'reference', 'notes', 'remboursement_date', 'recorded_by',
    ];

    protected $casts = [
        'montant'            => 'decimal:2',
        'remboursement_date' => 'date',
    ];

    public function pret(): BelongsTo
    {
        return $this->belongsTo(Pret::class);
    }

    public function modeReglement(): BelongsTo
    {
        return $this->belongsTo(ModeReglement::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
