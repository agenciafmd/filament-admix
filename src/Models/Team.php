<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Models;

use Agenciafmd\Admix\Database\Factories\TeamFactory;
use Agenciafmd\Admix\Traits\WithScopes;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Override;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * Team (tenant) of the admix panel, used when `filament-admix.tenancy.enabled` is on.
 */
#[UseFactory(TeamFactory::class)]
final class Team extends Model implements AuditableContract, HasAvatar
{
    use Auditable;

    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    use Prunable;
    use SoftDeletes;
    use WithScopes;

    /**
     * @var array<string, 'asc'|'desc'>
     */
    protected array $defaultSort = [
        'is_active' => 'desc',
        'name' => 'asc',
    ];

    public static function isTenancyEnabled(): bool
    {
        return (bool) config('filament-admix.tenancy.enabled', false);
    }

    /**
     * Slugs that teams cannot use, because the slug also names the subdomain of the team.
     *
     * @return array<int, string>
     */
    public static function reservedSlugs(): array
    {
        $slugs = config('filament-admix.tenancy.reserved_slugs', []);

        return collect(is_array($slugs) ? $slugs : [])
            ->filter(static fn (mixed $slug): bool => is_string($slug))
            ->values()
            ->all();
    }

    /**
     * Image shown in the tenant menu in place of the initials of the team.
     */
    public function getFilamentAvatarUrl(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        return Storage::url($this->avatar);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps();
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
        ];
    }
}
