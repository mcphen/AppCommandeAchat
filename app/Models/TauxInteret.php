<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TauxInteret extends Model
{
    protected $table = 'taux_interet';

    protected $fillable = ['label', 'taux_annuel', 'type', 'is_active', 'applique_depuis'];

    protected $casts = [
        'taux_annuel'     => 'decimal:2',
        'is_active'       => 'boolean',
        'applique_depuis' => 'date',
    ];

    public static function actifPourPret(): ?self
    {
        return static::where('type', 'pret')->where('is_active', true)->latest()->first();
    }

    public static function actifPourEpargne(): ?self
    {
        return static::where('type', 'epargne')->where('is_active', true)->latest()->first();
    }
}
