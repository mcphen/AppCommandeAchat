<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeuilValidation extends Model
{
    protected $table = 'seuils_validation';

    protected $fillable = ['niveau_validation_id', 'montant_seuil'];

    protected $casts = ['montant_seuil' => 'decimal:2'];

    public function niveauValidation(): BelongsTo
    {
        return $this->belongsTo(NiveauValidation::class);
    }
}
