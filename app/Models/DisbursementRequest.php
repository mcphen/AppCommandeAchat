<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DisbursementRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'reference',
        'user_id',
        'boutique_id',
        'nature_operation_id',
        'title',
        'description',
        'amount',
        'status',
        'payment_status',
        'current_level_order',
        'submitted_at',
    ];

    protected $casts = [
        'amount'              => 'decimal:2',
        'submitted_at'        => 'datetime',
        'current_level_order' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function booted(): void
    {
        static::creating(function (DisbursementRequest $dr) {
            if (empty($dr->uuid)) {
                $dr->uuid = Str::uuid()->toString();
            }

            if (empty($dr->reference)) {
                $year         = now()->year;
                $boutiqueCode = 'NA';

                if (! empty($dr->boutique_id)) {
                    $boutiqueCode = (string) (Boutique::query()->whereKey($dr->boutique_id)->value('code') ?? $dr->boutique_id);
                }

                $boutiqueCode = Str::upper((string) preg_replace('/[^A-Za-z0-9]/', '', $boutiqueCode));
                if ($boutiqueCode === '') {
                    $boutiqueCode = 'NA';
                }

                $prefix = 'DD-' . $boutiqueCode . '-' . $year . '-';
                $last   = static::withTrashed()
                    ->where('reference', 'like', $prefix . '%')
                    ->max('reference');
                $seq          = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;
                $dr->reference = $prefix . str_pad($seq, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function boutique(): BelongsTo
    {
        return $this->belongsTo(Boutique::class);
    }

    public function natureOperation(): BelongsTo
    {
        return $this->belongsTo(NatureOperation::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DisbursementRequestAttachment::class);
    }

    public function validationLogs(): MorphMany
    {
        return $this->morphMany(ValidationLog::class, 'validationable')->with(['validationLevel', 'user'])->latest();
    }

    public function currentLevel(): ?ValidationLevel
    {
        if (! $this->current_level_order) {
            return null;
        }

        return ValidationLevel::where('order', $this->current_level_order)->first();
    }

    public function decaissement(): HasOne
    {
        return $this->hasOne(Decaissement::class);
    }

    public function isDraft(): bool         { return $this->status === 'draft'; }
    public function isPending(): bool       { return $this->status === 'pending'; }
    public function isNeedsRevision(): bool { return $this->status === 'needs_revision'; }
    public function isApproved(): bool      { return $this->status === 'approved'; }
    public function isRejected(): bool      { return $this->status === 'rejected'; }
    public function isCancelled(): bool     { return $this->status === 'cancelled'; }
    public function isPaid(): bool          { return $this->payment_status === 'paid'; }
}
