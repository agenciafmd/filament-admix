<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Models;

use Agenciafmd\Admix\Database\Factories\RoleFactory;
use Agenciafmd\Admix\Traits\WithScopes;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[UseFactory(RoleFactory::class)]
final class Role extends Model implements AuditableContract
{
    use Auditable;
    use HasFactory;
    use SoftDeletes;
    use WithScopes;

    protected array $defaultSort = [
        'is_active' => 'desc',
        'name' => 'asc',
    ];

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->is_active
            && in_array($permission, $this->permissions ?? [], true);
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'permissions' => 'array',
        ];
    }
}
