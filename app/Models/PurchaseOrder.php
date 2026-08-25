<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'user_id',
        'circuit_id',
        'boutique_id',
        'fournisseur_id',
        'title',
        'description',
        'amount',
        'amount_ttc',
        'status',
        'current_level_order',
        'submitted_at',
        'order_number',
        'delivery_status',
        'ordered_at',
        'fully_received_at',
        'sage_reference',
        'source',
        'order_date',
        'project_code',
        'project_id',
        'last_reminder_sent_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_ttc' => 'decimal:2',
        'submitted_at' => 'datetime',
        'last_reminder_sent_at' => 'datetime',
        'ordered_at' => 'datetime',
        'fully_received_at' => 'datetime',
        'order_date' => 'date',
        'current_level_order' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function circuit(): BelongsTo
    {
        return $this->belongsTo(Circuit::class);
    }

    public function boutique(): BelongsTo
    {
        return $this->belongsTo(Boutique::class);
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PurchaseOrderAttachment::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class)->with(['article.category', 'fournisseur']);
    }

    public function receptions(): HasMany
    {
        return $this->hasMany(PurchaseOrderReception::class)->with(['receiver', 'lines'])->latest();
    }

    public function validationLogs(): HasMany
    {
        return $this->hasMany(ValidationLog::class)->with(['validationLevel', 'user'])->latest();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(OrderComment::class)->with('user')->oldest();
    }

    public function recalculateAmount(): void
    {
        $total = $this->lines()->selectRaw('SUM(quantity * unit_price) as total')->value('total') ?? 0;
        $this->updateQuietly(['amount' => $total]);
    }

    public function currentLevel(): ?ValidationLevel
    {
        if (! $this->current_level_order || ! $this->circuit_id) {
            return null;
        }

        return ValidationLevel::where('circuit_id', $this->circuit_id)
            ->where('order', $this->current_level_order)
            ->first();
    }

    public function isDraft(): bool         { return $this->status === 'draft'; }
    public function isPending(): bool       { return $this->status === 'pending'; }
    public function isNeedsRevision(): bool { return $this->status === 'needs_revision'; }
    public function isApproved(): bool      { return $this->status === 'approved'; }
    public function isRejected(): bool      { return $this->status === 'rejected'; }

    public function isOrdered(): bool           { return $this->delivery_status === 'ordered'; }
    public function isPartiallyReceived(): bool  { return $this->delivery_status === 'partially_received'; }
    public function isFullyReceived(): bool      { return $this->delivery_status === 'received'; }

    public function canBeOrdered(): bool
    {
        return $this->status === 'approved' && $this->delivery_status === null;
    }

    public function canReceive(): bool
    {
        return $this->status === 'approved'
            && in_array($this->delivery_status, ['ordered', 'partially_received']);
    }

    public function deliveryStatusLabel(): string
    {
        return match ($this->delivery_status) {
            'ordered'             => 'Commandée',
            'partially_received'  => 'Reçue partiellement',
            'received'            => 'Reçue entièrement',
            default               => '—',
        };
    }

    public function deliveryStatusColor(): string
    {
        return match ($this->delivery_status) {
            'ordered'             => 'blue',
            'partially_received'  => 'orange',
            'received'            => 'green',
            default               => 'gray',
        };
    }

    public function isEditableBy(User $user): bool
    {
        return $this->user_id === $user->id && in_array($this->status, ['draft', 'rejected', 'needs_revision']);
    }

    public function isResubmittable(): bool
    {
        return $this->status === 'needs_revision';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft'          => 'Brouillon',
            'pending'        => 'En attente',
            'needs_revision' => 'Révision demandée',
            'approved'       => 'Approuvée',
            'rejected'       => 'Refusée',
            default          => $this->status,
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'draft'          => 'gray',
            'pending'        => 'yellow',
            'needs_revision' => 'indigo',
            'approved'       => 'green',
            'rejected'       => 'red',
            default          => 'gray',
        };
    }
}
