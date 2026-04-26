<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModeReglement extends Model
{
    protected $table = 'modes_reglement';

    protected $fillable = ['name', 'slug', 'icon', 'is_active', 'order'];

    protected $casts = [
        'is_active' => 'boolean',
        'order'     => 'integer',
    ];

    public function decaissements(): HasMany
    {
        return $this->hasMany(Decaissement::class);
    }

    public static function actifs()
    {
        return static::where('is_active', true)->orderBy('order')->get();
    }
}
