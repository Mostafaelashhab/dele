<?php

namespace App\Models;

use App\Domain\Shared\Concerns\HasMedia;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'phone', 'password', 'locale', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasMedia, Notifiable, SoftDeletes;

    /**
     * Cached per-request so a page rendering many authorization checks does
     * not re-query the pivot table for every one of them.
     *
     * @var Collection<int, Role>|null
     */
    private ?Collection $loadedRoles = null;

    /**
     * Mirrors the database defaults.
     *
     * Middleware reads `is_active` on the authenticated user, which is not
     * always an instance that has been re-selected from the database, so the
     * default has to exist on the model as well as on the column.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'locale' => 'ar',
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            $user->ulid ??= (string) Str::ulid();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withPivot(['tenant_type', 'tenant_id'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<BusinessUser, $this>
     */
    public function businessMemberships(): HasMany
    {
        return $this->hasMany(BusinessUser::class);
    }

    /**
     * @return HasMany<CompanyUser, $this>
     */
    public function companyMemberships(): HasMany
    {
        return $this->hasMany(CompanyUser::class);
    }

    /**
     * @return BelongsToMany<Business, $this, Pivot>
     */
    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class, 'business_users')
            ->withPivot(['role', 'is_active', 'is_primary_contact'])
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<DeliveryCompany, $this, Pivot>
     */
    public function deliveryCompanies(): BelongsToMany
    {
        return $this->belongsToMany(DeliveryCompany::class, 'company_users')
            ->withPivot(['role', 'is_active', 'is_primary_contact'])
            ->withTimestamps();
    }

    /**
     * @return HasOne<Rider, $this>
     */
    public function rider(): HasOne
    {
        return $this->hasOne(Rider::class);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return Collection<int, Role>
     */
    public function cachedRoles(): Collection
    {
        return $this->loadedRoles ??= $this->roles()->get();
    }

    public function forgetCachedRoles(): void
    {
        $this->loadedRoles = null;
        $this->unsetRelation('roles');
    }

    public function hasRole(UserRole|string $role, ?string $tenantType = null, ?string $tenantId = null): bool
    {
        $slug = $role instanceof UserRole ? $role->value : $role;

        return $this->cachedRoles()->contains(function (Role $candidate) use ($slug, $tenantType, $tenantId): bool {
            if ($candidate->slug !== $slug) {
                return false;
            }

            if ($tenantType === null) {
                return true;
            }

            return $candidate->pivot->tenant_type === $tenantType
                && $candidate->pivot->tenant_id === $tenantId;
        });
    }

    /**
     * @param  array<int, UserRole|string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function isPlatformStaff(): bool
    {
        return $this->hasAnyRole(UserRole::platformRoles());
    }

    public function isPlatformAdmin(): bool
    {
        return $this->hasRole(UserRole::PlatformAdmin);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isPlatformAdmin()) {
            return true;
        }

        return $this->cachedRoles()
            ->loadMissing('permissions')
            ->flatMap(fn (Role $role) => $role->permissions->pluck('slug'))
            ->contains($permission);
    }

    /**
     * The dashboard this user should land on, derived from their highest role.
     */
    public function homeRoute(): string
    {
        if ($this->isPlatformStaff()) {
            return UserRole::PlatformAdmin->homeRoute();
        }

        if ($this->rider()->exists()) {
            return UserRole::Rider->homeRoute();
        }

        if ($this->companyMemberships()->where('is_active', true)->exists()) {
            return UserRole::CompanyOwner->homeRoute();
        }

        if ($this->businessMemberships()->where('is_active', true)->exists()) {
            return UserRole::BusinessOwner->homeRoute();
        }

        return 'login';
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn (string $part) => Str::substr($part, 0, 1))
            ->implode('');
    }
}
