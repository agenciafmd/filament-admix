<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Models;

use Agenciafmd\Admix\Database\Factories\UserFactory;
use Agenciafmd\Admix\Models\Scopes\AdmixTypeScope;
use Agenciafmd\Admix\Traits\WithScopes;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
final class User extends Authenticatable implements AuditableContract, FilamentUser, HasAvatar, MustVerifyEmail
{
    use Auditable;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;
    use Prunable;
    use SoftDeletes;
    use WithScopes;

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

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->type === 'admix' &&
            $this->is_active === true /*&&
            $this->hasVerifiedEmail()*/ ;
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
