<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Forms\Components;

use Agenciafmd\Admix\Permissions\PermissionRegistry;
use Filament\Forms\Components\Field;
use Override;

/**
 * Permission checkboxes laid out as a matrix: one row per resource,
 * one column per standard ability and a last column for extra abilities.
 */
final class PermissionMatrix extends Field
{
    protected string $view = 'filament-admix::filament.forms.components.permission-matrix';

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);

        $this->afterStateHydrated(static function (PermissionMatrix $component, mixed $state): void {
            $component->state(is_array($state) ? array_values($state) : []);
        });

        $this->dehydrateStateUsing(fn (mixed $state): array => collect(is_array($state) ? $state : [])
            ->filter(static fn (mixed $permission): bool => is_string($permission))
            ->intersect(collect($this->getGroups())
                ->flatMap(static fn (array $group): array => array_keys($group['permissions'])))
            ->values()
            ->all());
    }

    /**
     * @return array<int, array{resource: string, label: string, permissions: array<string, string>, abilities: array<string, string>, extra: array<string, string>}>
     */
    public function getGroups(): array
    {
        return $this->registry()->groups();
    }

    /**
     * @return array<string, string> ability => label
     */
    public function getAbilityColumns(): array
    {
        return $this->registry()->standardAbilities();
    }

    /**
     * @return array<string, array<int, string>> ability => permission keys of that column
     */
    public function getColumnPermissions(): array
    {
        return collect($this->getAbilityColumns())
            ->map(fn (string $label, string $ability): array => collect($this->getGroups())
                ->pluck("abilities.{$ability}")
                ->filter(static fn (mixed $permission): bool => is_string($permission))
                ->values()
                ->all())
            ->all();
    }

    public function hasExtraPermissions(): bool
    {
        return collect($this->getGroups())
            ->contains(fn (array $group): bool => $group['extra'] !== []);
    }

    private function registry(): PermissionRegistry
    {
        return resolve(PermissionRegistry::class);
    }
}
