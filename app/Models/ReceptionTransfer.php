<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ReceptionTransfer extends Model {
    protected $fillable = ['transfer_number', 'status', 'reception_id', 'project_id', 'project_responsible_id', 'transferred_by', 'transferred_at', 'reference', 'notes', 'confirmed_at', 'cancelled_at', 'cancelled_by', 'cancellation_reason', 'dispatch_signed_at', 'site_signed_at'];
    protected $casts = ['transferred_at' => 'datetime', 'confirmed_at' => 'datetime', 'cancelled_at' => 'datetime', 'dispatch_signed_at' => 'datetime', 'site_signed_at' => 'datetime'];
    protected static function booted(): void {
        static::created(function (self $transfer): void {
            if (! $transfer->transfer_number) {
                $transfer->forceFill(['transfer_number' => sprintf('BT-%s-%06d', $transfer->transferred_at->format('Y'), $transfer->id)])->saveQuietly();
            }
        });
    }
    public function reception(): BelongsTo { return $this->belongsTo(PurchaseOrderReception::class, 'reception_id'); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); } public function projectResponsible(): BelongsTo { return $this->belongsTo(User::class, 'project_responsible_id'); } public function canceller(): BelongsTo { return $this->belongsTo(User::class, 'cancelled_by'); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'transferred_by'); }
    public function lines(): HasMany { return $this->hasMany(ReceptionTransferLine::class, 'transfer_id'); }
}