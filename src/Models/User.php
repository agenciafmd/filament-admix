<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Models;

use Agenciafmd\Admix\Database\Factories\UserFactory;
use Agenciafmd\Admix\Models\Scopes\AdmixTypeScope;
use Agenciafmd\Admix\Traits\WithScopes;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Override;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[ScopedBy([AdmixTypeScope::class])]
#[UseFactory(UserFactory::class)]
#[Hidden([
    'api_token',
    'password',
    'remember_token',
    'type',
])]
final class User extends Authenticatable implements AuditableContract, FilamentUser, HasAvatar, HasDefaultTenant, HasTenants, MustVerifyEmail
{
    use Auditable;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;
    use Prunable;
    use SoftDeletes;
    use WithScopes;

    private const string IMPERSONATOR_SESSION_KEY = 'filament-admix.impersonator_id';

    #[Override]
    protected $attributes = [
        'type' => 'admix',
    ];

    /**
     * @var array<string, 'asc'|'desc'>
     */
    protected array $defaultSort = [
        'is_active' => 'desc',
        'name' => 'asc',
    ];

    public static function isImpersonating(): bool
    {
        return session()->has(self::IMPERSONATOR_SESSION_KEY);
    }

    /**
     * Logs in as the administrator again. Without an active administrator, logs out.
     */
    public static function leaveImpersonation(): ?self
    {
        $impersonatorId = session()->pull(self::IMPERSONATOR_SESSION_KEY);
        $impersonator = is_int($impersonatorId) ? self::query()->find($impersonatorId) : null;

        if (! $impersonator instanceof self || ! $impersonator->isAdmin() || ! $impersonator->is_active) {
            Filament::auth()->logout();

            return null;
        }

        Filament::auth()->login($impersonator);

        return $impersonator;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        return Storage::url($this->avatar);
    }

    /**
     * With the tenancy enabled, users with a role also need an active organization.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->type !== 'admix' || $this->is_active !== true /* || ! $this->hasVerifiedEmail() */) {
            return false;
        }

        if (! Organization::isTenancyEnabled() || $this->isAdmin()) {
            return true;
        }

        return $this->organizations()
            ->isActive()
            ->exists();
    }

    /**
     * Administrators access every organization; the other users, their active organizations.
     *
     * @return Collection<int, Organization>
     */
    public function getTenants(Panel $panel): Collection
    {
        if ($this->isAdmin()) {
            return Organization::query()
                ->sort()
                ->get();
        }

        return $this->organizations()
            ->isActive()
            ->sort()
            ->get();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if (! $tenant instanceof Organization) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        return $tenant->is_active
            && $this->organizations()
                ->whereKey($tenant->getKey())
                ->exists();
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        return $this->getTenants($panel)
            ->first();
    }

    /**
     * Whether the user also belongs to organizations other than the current one (tenancy enabled only).
     */
    public function belongsToOtherOrganizations(): bool
    {
        $organization = Filament::getTenant();

        if (! Organization::isTenancyEnabled() || ! $organization instanceof Organization) {
            return false;
        }

        return $this->organizations->contains(static fn (Organization $userOrganization): bool => ! $userOrganization->is($organization));
    }

    /**
     * @return BelongsToMany<Organization, $this>
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->withTimestamps();
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Users without a role are administrators and have every permission.
     */
    public function isAdmin(): bool
    {
        return $this->role_id === null;
    }

    /**
     * With `filament-admix.impersonate` enabled, administrators can access the panel as any other user with a role
     * that can access it.
     */
    public function canImpersonate(self $user): bool
    {
        $panel = Filament::getPanel('admix');

        return config()->boolean('filament-admix.impersonate', false)
            && $this->isAdmin()
            && ! $user->isAdmin()
            && ! $user->trashed()
            && $panel instanceof Panel
            && $user->canAccessPanel($panel);
    }

    /**
     * Logs in as the user and keeps the administrator in the session, to come back later.
     */
    public function impersonate(self $user): void
    {
        Filament::auth()->login($user);

        session()->put(self::IMPERSONATOR_SESSION_KEY, $this->getKey());
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->role?->hasPermission($permission) ?? false;
    }

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return self::query()
            ->where('deleted_at', '<=', today()->subDays(30));
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'role_id' => 'integer',
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
        ];
    }
}
