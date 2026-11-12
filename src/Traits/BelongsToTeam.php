<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Traits;

use Agenciafmd\Admix\Models\Team;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ties the model to a team (`team_id` column) when the tenancy is enabled.
 *
 * With a current team (`Filament::getTenant()`, also set outside the panel with
 * `Filament::setTenant()` in jobs and exports), queries only see the records of
 * that team and new records receive it. With the tenancy disabled, nothing changes.
 *
 * @mixin Model
 */
trait BelongsToTeam
{
    public static function bootBelongsToTeam(): void
    {
        static::addGlobalScope('team', static function (Builder $query): void {
            $team = self::currentTeam();

            if ($team instanceof Team) {
                $query->where($query->qualifyColumn('team_id'), $team->getKey());
            }
        });

        static::creating(static function (Model $model): void {
            $team = self::currentTeam();

            if ($team instanceof Team && $model->getAttribute('team_id') === null) {
                $model->setAttribute('team_id', $team->getKey());
            }
        });
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    private static function currentTeam(): ?Team
    {
        if (! Team::isTenancyEnabled()) {
            return null;
        }

        $tenant = Filament::getTenant();

        return $tenant instanceof Team ? $tenant : null;
    }
}
