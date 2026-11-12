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

    public function getFilamentAvatarUrl(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        return Storage::url($this->avatar);
    }

    /**
     * With the tenancy enabled, users with a role also need an active team.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->type !== 'admix' || $this->is_active !== true /* || ! $this->hasVerifiedEmail() */) {
            return false;
        }

        if (! Team::isTenancyEnabled() || $this->isAdmin()) {
            return true;
        }

        return $this->teams()
            ->isActive()
            ->exists();
    }

    /**
     * Administrators access every team; the other users, their active teams.
     *
     * @return Collection<int, Team>
     */
    public function getTenants(Panel $panel): Collection
    {
        if ($this->isAdmin()) {
            return Team::query()
                ->sort()
                ->get();
        }

        return $this->teams()
            ->isActive()
            ->sort()
            ->get();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if (! $tenant instanceof Team) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        return $tenant->is_active
            && $this->teams()
                ->whereKey($tenant->getKey())
                ->exists();
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        return $this->getTenants($panel)
            ->first();
    }

    /**
     * Whether the user also belongs to teams other than the current one (tenancy enabled only).
     */
    public function belongsToOtherTeams(): bool
    {
        $team = Filament::getTenant();

        if (! Team::isTenancyEnabled() || ! $team instanceof Team) {
            return false;
        }

        return $this->teams->contains(static fn (Team $userTeam): bool => ! $userTeam->is($team));
    }

    /**
     * @return BelongsToMany<Team, $this>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)
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
