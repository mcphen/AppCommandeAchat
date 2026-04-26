<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PretValidationLog extends Model
{
    protected $table = 'pret_validation_logs';

    protected $fillable = [
        'pret_id', 'validation_level_id', 'user_id',
        'action', 'comment', 'delegated_by_id',
    ];

    public function pret(): BelongsTo
    {
        return $this->belongsTo(Pret::class);
    }

    public function validationLevel(): BelongsTo
    {
        return $this->belongsTo(ValidationLevel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function delegatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_by_id');
    }
}
