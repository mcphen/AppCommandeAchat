<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalWorkflow extends Model
{
    protected $fillable = [
        'entreprise_id', 'categorie_achat', 'montant_min', 'montant_max', 'libelle',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class);
    }

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class);
    }
}
