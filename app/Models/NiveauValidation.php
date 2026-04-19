<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NiveauValidation extends Model
{
    protected $table = 'niveaux_validation';

    protected $fillable = ['nom', 'slug', 'ordre', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function seuil(): HasOne
    {
        return $this->hasOne(SeuilValidation::class);
    }

    public function validateurs(): HasMany
    {
        return $this->hasMany(Validateur::class);
    }

    public function validationsDap(): HasMany
    {
        return $this->hasMany(ValidationDap::class);
    }
}
