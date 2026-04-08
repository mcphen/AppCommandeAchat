<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ValidationLevel extends Model
{
    protected $fillable = ['name', 'order', 'description'];

    protected $casts = ['order' => 'integer'];

    public function validators(): HasMany
    {
        return $this->hasMany(User::class, 'validation_level_id');
    }

    public function validationLogs(): HasMany
    {
        return $this->hasMany(ValidationLog::class);
    }

    public static function nextAfter(int $order): ?self
    {
        return self::where('order', '>', $order)->orderBy('order')->first();
    }

    public static function first_level(): ?self
    {
        return self::orderBy('order')->first();
    }
}
