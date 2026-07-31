<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class AuditLog extends Model
{
    protected $fillable = [
        'actor_id',
        'target_user_id',
        'event',
        'description',
        'ip_address',
        'user_agent',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    /**
     * Enregistre un evenement de securite/administration (connexion, creation
     * de compte, changement de role ou de mot de passe, acces refuse...).
     */
    public static function record(string $event, ?string $description = null, ?User $target = null, ?User $actor = null): self
    {
        $request = request();
        $actor ??= $request?->user();

        return static::create([
            'actor_id'       => $actor?->getKey(),
            'target_user_id' => $target?->getKey(),
            'event'          => $event,
            'description'    => $description,
            'ip_address'     => $request instanceof Request ? $request->ip() : null,
            'user_agent'     => $request instanceof Request ? substr((string) $request->userAgent(), 0, 255) : null,
        ]);
    }
}
