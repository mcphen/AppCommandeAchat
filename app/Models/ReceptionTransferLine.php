<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ReceptionTransferLine extends Model {
    protected $fillable = ['transfer_id', 'reception_line_id', 'quantity_transferred'];
    protected $casts = ['quantity_transferred' => 'decimal:2'];
    public function transfer(): BelongsTo { return $this->belongsTo(ReceptionTransfer::class, 'transfer_id'); }
    public function receptionLine(): BelongsTo { return $this->belongsTo(PurchaseOrderReceptionLine::class, 'reception_line_id'); }
}