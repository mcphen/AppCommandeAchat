<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'sage_collaborateur_code',
        'role_id',
        'boutique_id',
        'signature_path',
        'onboarding_completed_at',
        'checklist_dismissed_at',
        'must_change_password',
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
            'must_change_password'     => 'boolean',
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

    public function validationLevels(): BelongsToMany
    {
        return $this->belongsToMany(ValidationLevel::class, 'user_validation_levels');
    }

    public function validationLevelForCircuit(int $circuitId): ?ValidationLevel
    {
        return $this->validationLevels->firstWhere('circuit_id', $circuitId);
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
     * Returns all validation levels this user can act on
     * (own levels, across all circuits + delegated levels).
     */
    public function validatableLevels(): Collection
    {
        $delegated = $this->activeDelegationsReceived()
            ->with('validationLevel')
            ->get()
            ->pluck('validationLevel')
            ->filter();

        return $this->validationLevels
            ->concat($delegated)
            ->unique('id')
            ->values();
    }

    /**
     * If the user is validating at $validationLevelId via delegation,
     * returns the delegator's id; otherwise null.
     */
    public function getDelegatorIdForLevel(int $validationLevelId): ?int
    {
        if ($this->validationLevels->contains('id', $validationLevelId)) {
            return null; // own level, not delegated
        }

        return $this->activeDelegationsReceived()
            ->get()
            ->first(fn ($d) => $d->validation_level_id === $validationLevelId)
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

    public function isFirstLevelValidatorOf(?int $circuitId): bool
    {
        if (! $circuitId || ! $this->isValidateur()) {
            return false;
        }

        return $this->validationLevelForCircuit($circuitId)?->order === 1;
    }

    public function canValidate(): bool
    {
        return $this->isAdmin() || $this->isValidateur();
    }
}
