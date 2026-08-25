<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderReceptionLine extends Model
{
    protected $fillable = [
        'reception_id',
        'purchase_order_line_id',
        'quantity_received',
    ];

    protected $casts = [
        'quantity_received' => 'decimal:2',
    ];

    public function reception(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderReception::class, 'reception_id');
    }

    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class, 'purchase_order_line_id');
    }

    public function transferLines(): HasMany
    {
        return $this->hasMany(ReceptionTransferLine::class, 'reception_line_id');
    }
}
