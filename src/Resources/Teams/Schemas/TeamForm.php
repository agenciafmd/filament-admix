<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Teams\Schemas;

use Agenciafmd\Admix\Models\User;
use Agenciafmd\Admix\Resources\Forms\Components\ImageUploadWithAutomaticallyResize;
use Agenciafmd\Admix\Resources\Infolists\Components\DateTimeEntry;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class TeamForm
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
                                ->schema(self::fields())
                                ->collapsible()
                                ->columns()
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
                                    ImageUploadWithAutomaticallyResize::make(
                                        name: 'avatar',
                                        directory: 'team/avatar',
                                        width: 500,
                                        height: 500,
                                    )
                                        ->avatar()
                                        ->alignCenter()
                                        ->columnSpanFull(),
                                    DateTimeEntry::make('created_at'),
                                    DateTimeEntry::make('updated_at'),
                                ])
                                ->columns()
                                ->collapsible(),
                        ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Fields shared by the edit form, the registration page and the "New team" modal of the tenant menu.
     *
     * @return array<int, TextInput|Select>
     */
    public static function fields(): array
    {
        return [
            TextInput::make('name')
                ->translateLabel()
                ->autofocus()
                ->minLength(3)
                ->maxLength(255)
                ->generateSlug()
                ->required(),
            TextInput::make('slug')
                ->translateLabel()
                ->alphaDash()
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->required(),
            Select::make('users')
                ->label(__('Users'))
                ->relationship('users', 'name')
                ->getOptionLabelFromRecordUsing(static fn (User $record): string => "{$record->name} ({$record->email})")
                ->multiple()
                ->searchable(['name', 'email'])
                ->preload()
                ->columnSpanFull(),
        ];
    }
}
