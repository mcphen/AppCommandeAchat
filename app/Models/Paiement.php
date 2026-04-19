<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paiement extends Model
{
    protected $fillable = [
        'dap_id', 'created_by', 'montant', 'date_paiement',
        'reference', 'mode_paiement', 'banque', 'notes',
    ];

    protected $casts = [
        'montant'       => 'decimal:2',
        'date_paiement' => 'date',
    ];

    const MODES = ['virement', 'cheque', 'espece', 'mobile_money'];

    public function dap(): BelongsTo
    {
        return $this->belongsTo(DemandeAutorisationPaiement::class, 'dap_id');
    }

    public function saisie_par(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
