<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entreprise extends Model
{
    protected $fillable = ['groupe_id', 'nom', 'code', 'adresse', 'ville', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function groupe(): BelongsTo
    {
        return $this->belongsTo(Groupe::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function budgetsAnnuels(): HasMany
    {
        return $this->hasMany(BudgetAnnuel::class);
    }

    public function expressionsBesoin(): HasMany
    {
        return $this->hasMany(ExpressionBesoin::class);
    }

    public function approvalWorkflows(): HasMany
    {
        return $this->hasMany(ApprovalWorkflow::class);
    }
}
