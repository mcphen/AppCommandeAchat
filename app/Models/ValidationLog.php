<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidationLog extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'validation_level_id',
        'user_id',
        'action',
        'comment',
    ];

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

    public function isApproved(): bool { return $this->action === 'approved'; }
    public function isRejected(): bool { return $this->action === 'rejected'; }

    public function actionLabel(): string
    {
        return $this->action === 'approved' ? 'Approuvée' : 'Refusée';
    }
}
