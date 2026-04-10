<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class OrderComment extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'user_id',
        'type',
        'content',
        'attachment_path',
        'attachment_name',
        'attachment_size',
    ];

    protected $casts = [
        'attachment_size' => 'integer',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRevisionRequest(): bool
    {
        return $this->type === 'revision_request';
    }

    public function hasAttachment(): bool
    {
        return $this->attachment_path !== null;
    }

    public function formattedSize(): string
    {
        if (! $this->attachment_size) return '';
        if ($this->attachment_size >= 1048576) {
            return number_format($this->attachment_size / 1048576, 1) . ' MB';
        }
        return number_format($this->attachment_size / 1024, 0) . ' KB';
    }

    public function deleteAttachment(): void
    {
        if ($this->attachment_path) {
            Storage::disk('private')->delete($this->attachment_path);
        }
    }
}
