<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderLine extends Model
{
    protected $fillable = [
        'purchase_order_id', 'article_id', 'fournisseur_id',
        'quantity', 'unit_price', 'note',
    ];

    protected $casts = [
        'quantity'   => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    protected $appends = ['subtotal', 'quantity_received_total'];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function receptionLines(): HasMany
    {
        return $this->hasMany(PurchaseOrderReceptionLine::class, 'purchase_order_line_id');
    }

    public function getSubtotalAttribute(): float
    {
        return round((float) $this->quantity * (float) $this->unit_price, 2);
    }

    public function getQuantityReceivedTotalAttribute(): float
    {
        // Uses loaded relation to avoid N+1
        if ($this->relationLoaded('receptionLines')) {
            return (float) $this->receptionLines->sum('quantity_received');
        }
        return (float) $this->receptionLines()->sum('quantity_received');
    }
}
