<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Policies;

use Agenciafmd\Admix\Models\User;
use Agenciafmd\Admix\Permissions\PermissionRegistry;
use Illuminate\Database\Eloquent\Model;

/**
 * Generic policy registered for every model of the admix panel resources.
 *
 * Filament only authorizes through a policy when the method exists, so every
 * method is declared explicitly. The decision is taken in `before()`, which
 * receives the model (or model class) needed to resolve the resource.
 */
final class ResourcePolicy
{
    public function __construct(private readonly PermissionRegistry $registry) {}

    public function before(mixed $user, string $ability, Model|string|null $model = null): bool
    {
        if (! $user instanceof User || $model === null) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        // Only administrators can change administrators.
        if ($model instanceof User && $model->isAdmin() && ! in_array($ability, ['viewAny', 'view'], true)) {
            return false;
        }

        if ($ability === 'restoreAudit') {
            return $this->allows($user, 'audit', $model)
                && $this->allows($user, 'update', $model);
        }

        return $this->allows($user, $ability, $model);
    }

    public function viewAny(): bool
    {
        return false;
    }

    public function view(): bool
    {
        return false;
    }

    public function create(): bool
    {
        return false;
    }

    public function replicate(): bool
    {
        return false;
    }

    public function update(): bool
    {
        return false;
    }

    public function reorder(): bool
    {
        return false;
    }

    public function delete(): bool
    {
        return false;
    }

    public function deleteAny(): bool
    {
        return false;
    }

    public function forceDelete(): bool
    {
        return false;
    }

    public function forceDeleteAny(): bool
    {
        return false;
    }

    public function restore(): bool
    {
        return false;
    }

    public function restoreAny(): bool
    {
        return false;
    }

    public function audit(): bool
    {
        return false;
    }

    public function restoreAudit(): bool
    {
        return false;
    }

    private function allows(User $user, string $ability, Model|string $model): bool
    {
        $permission = $this->registry->permissionFor($ability, $model);

        return $permission !== null && $user->hasPermission($permission);
    }
}
