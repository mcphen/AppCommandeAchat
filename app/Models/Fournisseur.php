<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fournisseur extends Model
{
    protected $fillable = ['name', 'code', 'sage_code', 'email', 'phone', 'address', 'city', 'is_active', 'is_approved'];

    protected $casts = [
        'is_active'   => 'boolean',
        'is_approved' => 'boolean',
    ];

    public function orderLines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    public function catalogueArticles(): HasMany
    {
        return $this->hasMany(FournisseurArticle::class);
    }
}
