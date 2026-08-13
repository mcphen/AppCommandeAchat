<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Le code chantier vient d'un champ texte libre Sage (DO_Ref) : pas de nom distinct
     * disponible cote Sage. On cree le chantier avec le code comme nom par defaut ; le nom
     * pourra ensuite etre corrige manuellement dans l'app sans etre ecrase par les
     * resynchronisations suivantes (meme logique que fournisseur/article).
     */
    public static function findOrCreateFromCode(string $code): self
    {
        $code = trim($code);

        return self::firstOrCreate(
            ['code' => $code],
            ['name' => $code, 'is_active' => true]
        );
    }
}
