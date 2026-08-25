<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ReceptionTransfer extends Model {
    protected $fillable = ['reception_id', 'project_id', 'transferred_by', 'transferred_at', 'reference', 'notes'];
    protected $casts = ['transferred_at' => 'datetime'];
    public function reception(): BelongsTo { return $this->belongsTo(PurchaseOrderReception::class, 'reception_id'); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'transferred_by'); }
    public function lines(): HasMany { return $this->hasMany(ReceptionTransferLine::class, 'transfer_id'); }
}