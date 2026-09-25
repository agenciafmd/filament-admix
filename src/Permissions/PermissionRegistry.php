<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Permissions;

use Filament\Facades\Filament;
use Filament\Resources\Resource as FilamentResource;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;
use UnitEnum;

/**
 * Builds the permission list from the resources registered in the admix panel,
 * so packages get access control without any configuration.
 *
 * A permission key has the format "{ResourceClass}@{ability}".
 */
final class PermissionRegistry
{
    /**
     * Maps each policy method called by Filament to the ability that grants it.
     *
     * @var array<string, string>
     */
    public const array ABILITY_MAP = [
        'viewAny' => 'view',
        'view' => 'view',
        'create' => 'create',
        'replicate' => 'create',
        'update' => 'update',
        'reorder' => 'update',
        'delete' => 'delete',
        'deleteAny' => 'delete',
        'forceDelete' => 'delete',
        'forceDeleteAny' => 'delete',
        'restore' => 'restore',
        'restoreAny' => 'restore',
        'audit' => 'audit',
    ];

    /**
     * @var array<class-string<Model>, class-string<FilamentResource>>|null
     */
    private ?array $resourcesByModel = null;

    public function __construct(private readonly string $panelId = 'admix') {}

    public static function permissionKey(string $resource, string $ability): string
    {
        return $resource . '@' . $ability;
    }

    /**
     * @return array<class-string<Model>, class-string<FilamentResource>>
     */
    public function resourcesByModel(): array
    {
        if ($this->resourcesByModel !== null) {
            return $this->resourcesByModel;
        }

        $resourcesByModel = [];

        foreach (Filament::getPanels()[$this->panelId]?->getResources() ?? [] as $resource) {
            $resourcesByModel[$resource::getModel()] ??= $resource;
        }

        return $this->resourcesByModel = $resourcesByModel;
    }

    /**
     * @return class-string<FilamentResource>|null
     */
    public function resourceFor(Model|string $model): ?string
    {
        $modelClass = $model instanceof Model ? $model::class : $model;

        return $this->resourcesByModel()[$modelClass] ?? null;
    }

    /**
     * @param  class-string<FilamentResource>  $resource
     * @return array<string, string> ability => label
     */
    public function abilitiesFor(string $resource): array
    {
        return [
            ...$this->standardAbilitiesFor($resource),
            ...$this->extraAbilitiesFor($resource),
        ];
    }

    /**
     * The standard abilities, in display order.
     *
     * @return array<string, string> ability => label
     */
    public function standardAbilities(): array
    {
        return [
            'view' => __('view'),
            'create' => __('create'),
            'update' => __('update'),
            'delete' => __('delete'),
            'restore' => __('restore'),
            'audit' => __('audit'),
        ];
    }

    /**
     * @param  class-string<FilamentResource>  $resource
     * @return array<string, string> ability => label
     */
    public function standardAbilitiesFor(string $resource): array
    {
        return collect($this->standardAbilities())
            ->filter(fn (string $label, string $ability): bool => match ($ability) {
                'restore' => in_array(SoftDeletes::class, class_uses_recursive($resource::getModel()), true),
                'audit' => in_array(AuditsRelationManager::class, $resource::getRelations(), true),
                default => true,
            })
            ->all();
    }

    /**
     * Resources may declare extra abilities with `public static function getExtraPermissions(): array`
     * returning `['ability' => 'label']`.
     *
     * @param  class-string<FilamentResource>  $resource
     * @return array<string, string>
     */
    public function extraAbilitiesFor(string $resource): array
    {
        if (! method_exists($resource, 'getExtraPermissions')) {
            return [];
        }

        return $resource::getExtraPermissions();
    }

    /**
     * @return array<int, array{resource: class-string<FilamentResource>, label: string, permissions: array<string, string>, abilities: array<string, string>, extra: array<string, string>}>
     */
    public function groups(): array
    {
        return collect($this->resourcesByModel())
            ->values()
            ->sortBy(fn (string $resource): string => sprintf(
                '%s|%05d|%s',
                $this->navigationGroupLabel($resource) ?? '',
                $resource::getNavigationSort() ?? 99999,
                $resource::getPluralModelLabel(),
            ))
            ->map(fn (string $resource): array => [
                'resource' => $resource,
                'label' => collect([
                    $this->navigationGroupLabel($resource),
                    $resource::getPluralModelLabel(),
                ])
                    ->filter()
                    ->implode(' » '),
                'permissions' => collect($this->abilitiesFor($resource))
                    ->mapWithKeys(fn (string $label, string $ability): array => [
                        self::permissionKey($resource, $ability) => $label,
                    ])
                    ->all(),
                'abilities' => collect($this->standardAbilitiesFor($resource))
                    ->mapWithKeys(fn (string $label, string $ability): array => [
                        $ability => self::permissionKey($resource, $ability),
                    ])
                    ->all(),
                'extra' => collect($this->extraAbilitiesFor($resource))
                    ->mapWithKeys(fn (string $label, string $ability): array => [
                        self::permissionKey($resource, $ability) => $label,
                    ])
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Resolves the permission key for a policy method (or extra ability) against a model.
     */
    public function permissionFor(string $ability, Model|string $model): ?string
    {
        $resource = $this->resourceFor($model);

        if ($resource === null) {
            return null;
        }

        $mappedAbility = self::ABILITY_MAP[$ability] ?? null;

        if ($mappedAbility === null && array_key_exists($ability, $this->extraAbilitiesFor($resource))) {
            $mappedAbility = $ability;
        }

        if ($mappedAbility === null) {
            return null;
        }

        return self::permissionKey($resource, $mappedAbility);
    }

    /**
     * @param  class-string<FilamentResource>  $resource
     */
    private function navigationGroupLabel(string $resource): ?string
    {
        $group = $resource::getNavigationGroup();

        return match (true) {
            $group instanceof HasLabel => (string) $group->getLabel(),
            $group instanceof UnitEnum => $group->name,
            default => $group,
        };
    }
}
