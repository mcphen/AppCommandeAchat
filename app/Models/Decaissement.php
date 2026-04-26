<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Decaissement extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'recorded_by',
        'montant',
        'mode_reglement_id',
        'reference',
        'notes',
        'decaissement_date',
    ];

    protected $casts = [
        'montant'           => 'decimal:2',
        'decaissement_date' => 'date',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function modeReglement(): BelongsTo
    {
        return $this->belongsTo(ModeReglement::class);
    }
}
