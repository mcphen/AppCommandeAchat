<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ValidationLevel extends Model
{
    protected $fillable = ['circuit_id', 'name', 'order', 'description', 'type'];

    protected $casts = ['order' => 'integer'];

    public function circuit(): BelongsTo
    {
        return $this->belongsTo(Circuit::class);
    }

    public function isApproval(): bool
    {
        return $this->type === 'approbation';
    }

    public function actionNoun(): string
    {
        return $this->isApproval() ? 'approbation' : 'validation';
    }

    public function actionPastParticiple(): string
    {
        return $this->isApproval() ? 'approuvée' : 'validée';
    }

    public function validators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_validation_levels');
    }

    public function validationLogs(): HasMany
    {
        return $this->hasMany(ValidationLog::class);
    }

    public static function nextAfter(int $order, int $circuitId): ?self
    {
        return self::where('circuit_id', $circuitId)->where('order', '>', $order)->orderBy('order')->first();
    }

    public static function first_level(int $circuitId): ?self
    {
        return self::where('circuit_id', $circuitId)->orderBy('order')->first();
    }
}
