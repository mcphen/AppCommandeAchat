<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingEntry extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'reception_id',
        'entry_date',
        'journal_code',
        'piece_ref',
        'account_code',
        'account_label',
        'aux_code',
        'aux_label',
        'entry_label',
        'debit',
        'credit',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'debit'      => 'decimal:2',
        'credit'     => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function reception(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderReception::class, 'reception_id');
    }
}
