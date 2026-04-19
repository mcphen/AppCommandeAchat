<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Groupe extends Model
{
    protected $fillable = ['nom', 'code', 'logo_path', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function entreprises(): HasMany
    {
        return $this->hasMany(Entreprise::class);
    }
}
