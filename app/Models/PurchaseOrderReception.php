<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderReception extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'received_by',
        'received_at',
        'type',
        'notes',
        'invoice_number',
        'invoice_date',
        'invoice_amount',
    ];

    protected $casts = [
        'received_at'    => 'datetime',
        'invoice_date'   => 'date',
        'invoice_amount' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderReceptionLine::class, 'reception_id');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(ReceptionTransfer::class, 'reception_id');
    }

    public function typeLabel(): string
    {
        return $this->type === 'complete' ? 'Complète' : 'Partielle';
    }
}
