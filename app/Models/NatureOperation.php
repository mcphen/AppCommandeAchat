<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NatureOperation extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (self $natureOperation) {
            if (filled($natureOperation->name)) {
                $natureOperation->slug = static::makeSlug($natureOperation->name);
            }
        });
    }

    public static function makeSlug(string $name): string
    {
        $slug = Str::slug($name);

        if ($slug !== '') {
            return $slug;
        }

        return (string) Str::of($name)
            ->trim()
            ->lower()
            ->replaceMatches('/\s+/', '-');
    }

    public function disbursementRequests()
    {
        return $this->hasMany(DisbursementRequest::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
