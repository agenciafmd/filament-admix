<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Roles\Schemas;

use Agenciafmd\Admix\Models\Role;
use Agenciafmd\Admix\Permissions\PermissionRegistry;
use Agenciafmd\Admix\Resources\Infolists\Components\DateTimeEntry;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class RoleForm
{
    public const string PERMISSION_GROUPS = 'permission_groups';

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        Group::make([
                            Section::make(__('General'))
                                ->schema([
                                    TextInput::make('name')
                                        ->translateLabel()
                                        ->autofocus()
                                        ->minLength(3)
                                        ->maxLength(255)
                                        ->required(),
                                ])
                                ->collapsible()
                                ->columnSpan(2),
                        ])
                            ->columnSpan(2),
                        Group::make([
                            Section::make(__('Information'))
                                ->schema([
                                    Toggle::make('is_active')
                                        ->translateLabel()
                                        ->default(true)
                                        ->columnSpanFull(),
                                    DateTimeEntry::make('created_at'),
                                    DateTimeEntry::make('updated_at'),
                                ])
                                ->columns()
                                ->collapsible(),
                        ]),
                        Section::make(__('Permissions'))
                            ->schema(self::permissionFields())
                            ->columns(4)
                            ->collapsible()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Collapses the per-resource checkbox lists into the `permissions` column.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mergePermissions(array $data): array
    {
        $data['permissions'] = collect($data[self::PERMISSION_GROUPS] ?? [])
            ->flatten()
            ->filter()
            ->unique()
            ->values()
            ->all();

        unset($data[self::PERMISSION_GROUPS]);

        return $data;
    }

    /**
     * @return array<int, CheckboxList>
     */
    private static function permissionFields(): array
    {
        return collect(resolve(PermissionRegistry::class)->groups())
            ->map(fn (array $group): CheckboxList => CheckboxList::make(self::PERMISSION_GROUPS . '.' . str($group['resource'])->replace('\\', '_'))
                ->label($group['label'])
                ->options($group['permissions'])
                ->afterStateHydrated(function (CheckboxList $component, ?Role $record) use ($group): void {
                    $component->state(array_values(array_intersect(
                        array_keys($group['permissions']),
                        $record?->permissions ?? [],
                    )));
                })
                ->bulkToggleable())
            ->all();
    }
}
