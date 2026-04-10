<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'validation_level_id',
        'boutique_id',
        'onboarding_completed_at',
        'checklist_dismissed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'        => 'datetime',
            'onboarding_completed_at'  => 'datetime',
            'checklist_dismissed_at'   => 'datetime',
            'password'                 => 'hashed',
        ];
    }

    public function needsOnboarding(): bool
    {
        return $this->onboarding_completed_at === null;
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function validationLevel(): BelongsTo
    {
        return $this->belongsTo(ValidationLevel::class);
    }

    public function boutique(): BelongsTo
    {
        return $this->belongsTo(Boutique::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function validationLogs(): HasMany
    {
        return $this->hasMany(ValidationLog::class);
    }

    public function delegationsGiven(): HasMany
    {
        return $this->hasMany(ValidationDelegation::class, 'delegator_id');
    }

    public function delegationsReceived(): HasMany
    {
        return $this->hasMany(ValidationDelegation::class, 'delegatee_id');
    }

    public function activeDelegationsReceived(): HasMany
    {
        return $this->delegationsReceived()
            ->where('is_active', true)
            ->whereDate('starts_at', '<=', now())
            ->whereDate('ends_at', '>=', now());
    }

    /**
     * Returns all validation level orders this user can act on
     * (own level + delegated levels).
     */
    public function validatableLevelOrders(): array
    {
        $orders = [];

        if ($this->validationLevel) {
            $orders[] = $this->validationLevel->order;
        }

        $delegated = $this->activeDelegationsReceived()
            ->with('validationLevel')
            ->get()
            ->pluck('validationLevel.order')
            ->filter()
            ->toArray();

        return array_values(array_unique(array_merge($orders, $delegated)));
    }

    /**
     * If the user is validating at $levelOrder via delegation,
     * returns the delegator's id; otherwise null.
     */
    public function getDelegatorIdForLevel(int $levelOrder): ?int
    {
        if ($this->validationLevel?->order === $levelOrder) {
            return null; // own level, not delegated
        }

        return $this->activeDelegationsReceived()
            ->with('validationLevel')
            ->get()
            ->first(fn ($d) => $d->validationLevel?->order === $levelOrder)
            ?->delegator_id;
    }

    public function isAdmin(): bool
    {
        return $this->role?->slug === 'admin';
    }

    public function isDemandeur(): bool
    {
        return $this->role?->slug === 'demandeur';
    }

    public function isValidateur(): bool
    {
        return $this->role?->slug === 'validateur';
    }

    public function canValidate(): bool
    {
        return $this->isAdmin() || $this->isValidateur();
    }
}
