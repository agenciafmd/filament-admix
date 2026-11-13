<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Traits;

use Agenciafmd\Admix\Models\Organization;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ties the model to a organization (`organization_id` column) when the tenancy is enabled.
 *
 * With a current organization (`Filament::getTenant()`, also set outside the panel with
 * `Filament::setTenant()` in jobs and exports), queries only see the records of
 * that organization and new records receive it. With the tenancy disabled, nothing changes.
 *
 * @mixin Model
 */
trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope('organization', static function (Builder $query): void {
            $organization = self::currentOrganization();

            if ($organization instanceof Organization) {
                $query->where($query->qualifyColumn('organization_id'), $organization->getKey());
            }
        });

        static::creating(static function (Model $model): void {
            $organization = self::currentOrganization();

            if ($organization instanceof Organization && $model->getAttribute('organization_id') === null) {
                $model->setAttribute('organization_id', $organization->getKey());
            }
        });
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    private static function currentOrganization(): ?Organization
    {
        if (! Organization::isTenancyEnabled()) {
            return null;
        }

        $tenant = Filament::getTenant();

        return $tenant instanceof Organization ? $tenant : null;
    }
}
