<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Roles\Schemas;

use Agenciafmd\Admix\Resources\Forms\Components\PermissionMatrix;
use Agenciafmd\Admix\Resources\Infolists\Components\DateTimeEntry;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        Group::make([
                            Section::make(__('General'))
                                ->key('general')
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
                                ->key('information')
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
                            ->key('permissions')
                            ->schema(
                                Schema::make()
                                    ->components([
                                        PermissionMatrix::make('permissions')
                                            ->hiddenLabel(),
                                    ])
                                    ->lazyLoaded(),
                            )
                            ->collapsible()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
