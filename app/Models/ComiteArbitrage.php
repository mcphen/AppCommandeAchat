<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComiteArbitrage extends Model
{
    protected $table = 'comites_arbitrage';

    protected $fillable = [
        'nom', 'description', 'entreprise_id', 'quorum_pct', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'quorum_pct' => 'integer',
    ];

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function membres(): HasMany
    {
        return $this->hasMany(MembreComiteArbitrage::class, 'comite_arbitrage_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(SessionArbitrage::class, 'comite_arbitrage_id');
    }

    public function getQuorumCountAttribute(): int
    {
        $total = $this->membres()->where('is_active', true)->count();
        return max(1, (int) ceil($total * $this->quorum_pct / 100));
    }
}
