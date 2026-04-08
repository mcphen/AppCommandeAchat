<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'amount',
        'status',
        'current_level_order',
        'submitted_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'current_level_order' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PurchaseOrderAttachment::class);
    }

    public function validationLogs(): HasMany
    {
        return $this->hasMany(ValidationLog::class)->with(['validationLevel', 'user'])->latest();
    }

    public function currentLevel(): ?ValidationLevel
    {
        if (! $this->current_level_order) {
            return null;
        }

        return ValidationLevel::where('order', $this->current_level_order)->first();
    }

    public function isDraft(): bool    { return $this->status === 'draft'; }
    public function isPending(): bool  { return $this->status === 'pending'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isRejected(): bool { return $this->status === 'rejected'; }

    public function isEditableBy(User $user): bool
    {
        return $this->user_id === $user->id && in_array($this->status, ['draft', 'rejected']);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft'    => 'Brouillon',
            'pending'  => 'En attente',
            'approved' => 'Approuvée',
            'rejected' => 'Refusée',
            default    => $this->status,
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'draft'    => 'gray',
            'pending'  => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            default    => 'gray',
        };
    }
}
