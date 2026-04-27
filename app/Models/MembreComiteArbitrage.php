<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembreComiteArbitrage extends Model
{
    protected $table = 'membres_comite_arbitrage';

    protected $fillable = [
        'comite_arbitrage_id', 'user_id', 'role_membre', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function comite(): BelongsTo
    {
        return $this->belongsTo(ComiteArbitrage::class, 'comite_arbitrage_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPresident(): bool
    {
        return $this->role_membre === 'president';
    }
}
