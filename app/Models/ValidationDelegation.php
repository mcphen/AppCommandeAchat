<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidationDelegation extends Model
{
    protected $fillable = [
        'delegator_id',
        'delegatee_id',
        'validation_level_id',
        'starts_at',
        'ends_at',
        'reason',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at'   => 'date',
        'is_active' => 'boolean',
    ];

    public function delegator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegator_id');
    }

    public function delegatee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegatee_id');
    }

    public function validationLevel(): BelongsTo
    {
        return $this->belongsTo(ValidationLevel::class);
    }

    public function isCurrentlyActive(): bool
    {
        return $this->is_active
            && $this->starts_at->lte(now())
            && $this->ends_at->gte(now()->startOfDay());
    }

    public function statusLabel(): string
    {
        if (! $this->is_active) return 'Désactivée';
        if ($this->ends_at->lt(now()->startOfDay())) return 'Expirée';
        if ($this->starts_at->gt(now())) return 'À venir';
        return 'Active';
    }
}
