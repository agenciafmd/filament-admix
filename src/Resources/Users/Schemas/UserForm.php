<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Users\Schemas;

use Agenciafmd\Admix\Resources\Schemas\Components\DateTimePickerDisabled;
use Agenciafmd\Admix\Resources\Schemas\Components\ImageUploadWithDefault;
use Agenciafmd\Admix\Resources\Schemas\Components\PasswordInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('General'))
                    ->schema([
                        TextInput::make('name')
                            ->translateLabel()
                            ->autofocus()
                            ->minLength(3)
                            ->maxLength(255)
                            ->required(),
                        TextInput::make('email')
                            ->translateLabel()
                            ->rules([
                                'email:rfc,dns',
                            ])
                            ->unique(ignoreRecord: true)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Page $livewire) => $livewire->validateOnly('data.email'))
                            ->required(),
                        PasswordInput::make()
                            ->columnSpan(1),
                        ImageUploadWithDefault::make(name: 'avatar', directory: 'user/avatar')
                            ->avatar(),
                    ])
                    ->collapsible()
                    ->columns()
                    ->columnSpan(2),
                Section::make(__('Information'))
                    ->schema([
                        Toggle::make('is_active')
                            ->translateLabel()
                            ->default(true)
                            ->columnSpan(2),
                        DateTimePickerDisabled::make('created_at'),
                        DateTimePickerDisabled::make('updated_at'),
                    ])
                    ->columns()
                    ->collapsible(),
            ])
            ->columns(3);
    }
}
