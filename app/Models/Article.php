<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    protected $fillable = [
        'category_id', 'name', 'reference', 'description',
        'unit', 'unit_price', 'is_active', 'nature',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'unit_price' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderLines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    public function prixFournisseurs(): HasMany
    {
        return $this->hasMany(FournisseurArticle::class);
    }
}
