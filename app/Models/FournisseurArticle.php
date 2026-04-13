<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FournisseurArticle extends Model
{
    protected $table = 'fournisseur_articles';

    protected $fillable = [
        'fournisseur_id',
        'article_id',
        'unit_price',
        'reference_fournisseur',
        'delai_livraison_jours',
        'valide_jusqu_au',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'unit_price'           => 'decimal:2',
        'delai_livraison_jours' => 'integer',
        'valide_jusqu_au'      => 'date',
        'is_active'            => 'boolean',
    ];

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
