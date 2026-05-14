<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisbursementRequestAttachment extends Model
{
    protected $fillable = [
        'disbursement_request_id',
        'file_path',
        'file_name',
        'file_size',
    ];

    public function disbursementRequest(): BelongsTo
    {
        return $this->belongsTo(DisbursementRequest::class);
    }
}
