<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Validateur extends Model
{
    protected $fillable = ['user_id', 'niveau_validation_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function niveauValidation(): BelongsTo
    {
        return $this->belongsTo(NiveauValidation::class);
    }
}
