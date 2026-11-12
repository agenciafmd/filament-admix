<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Policies;

use Agenciafmd\Admix\Models\Team;
use Agenciafmd\Admix\Models\User;
use Agenciafmd\Admix\Permissions\PermissionRegistry;
use Agenciafmd\Admix\Traits\BelongsToTeam;
use Illuminate\Database\Eloquent\Model;

/**
 * Generic policy registered for every model of the admix panel resources.
 *
 * Filament only authorizes through a policy when the method exists, so every
 * method is declared explicitly. The decision is taken in `before()`, which
 * receives the model (or model class) needed to resolve the resource.
 */
final readonly class ResourcePolicy
{
    public function __construct(private PermissionRegistry $registry) {}

    public function before(mixed $user, string $ability, Model|string|null $model = null): bool
    {
        if (! $user instanceof User || $model === null) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($this->registry->isAdministratorsOnly($model)) {
            return false;
        }

        if ($model instanceof Model && ! $this->belongsToUserTeams($user, $model)) {
            return false;
        }

        // Only administrators can change administrators.
        if ($model instanceof User && $model->isAdmin() && ! in_array($ability, ['viewAny', 'view'], true)) {
            return false;
        }

        // Users shared with other teams can only be seen or removed from the current team.
        if ($model instanceof User && $model->belongsToOtherTeams() && ! in_array($ability, ['viewAny', 'view', 'delete'], true)) {
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

    /**
     * With the tenancy enabled, records of models with `BelongsToTeam` are only reachable
     * by members of their team, even outside the panel scope (callbacks, jobs).
     */
    private function belongsToUserTeams(User $user, Model $model): bool
    {
        if (! Team::isTenancyEnabled() || ! in_array(BelongsToTeam::class, class_uses_recursive($model), true)) {
            return true;
        }

        $teamId = $model->getAttribute('team_id');

        return $teamId !== null && $user->teams->contains('id', $teamId);
    }

    private function allows(User $user, string $ability, Model|string $model): bool
    {
        $permission = $this->registry->permissionFor($ability, $model);

        return $permission !== null && $user->hasPermission($permission);
    }
}
