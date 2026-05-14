<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ValidationLog extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'validationable_type',
        'validationable_id',
        'validation_level_id',
        'user_id',
        'action',
        'comment',
        'delegated_by_id',
    ];

    public function validationable(): MorphTo
    {
        return $this->morphTo();
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function validationLevel(): BelongsTo
    {
        return $this->belongsTo(ValidationLevel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function delegatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_by_id');
    }

    public function isDelegated(): bool
    {
        return $this->delegated_by_id !== null;
    }

    public function isApproved(): bool { return $this->action === 'approved'; }
    public function isRejected(): bool { return $this->action === 'rejected'; }

    public function actionLabel(): string
    {
        return $this->action === 'approved' ? 'Approuvée' : 'Refusée';
    }
}
