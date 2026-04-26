<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['name', 'slug'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isAdmin(): bool
    {
        return $this->slug === 'admin';
    }

    public function isDemandeur(): bool
    {
        return $this->slug === 'demandeur';
    }

    public function isValidateur(): bool
    {
        return $this->slug === 'validateur';
    }

    public function isCaissier(): bool
    {
        return $this->slug === 'caissier';
    }

    public function isAgent(): bool
    {
        return $this->slug === 'agent';
    }
}
