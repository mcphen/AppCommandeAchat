<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderAttachment extends Model
{
    protected $fillable = ['purchase_order_id', 'file_path', 'file_name', 'file_size'];

    protected $casts = ['file_size' => 'integer'];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function formattedSize(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        return number_format($bytes / 1024, 2) . ' KB';
    }
}
