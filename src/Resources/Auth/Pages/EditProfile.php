<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Auth\Pages;

use Agenciafmd\Admix\Resources\Schemas\Components\DateTimePickerDisabled;
use Agenciafmd\Admix\Resources\Schemas\Components\ImageUploadWithDefault;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->inlineLabel(false)
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
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                        ImageUploadWithDefault::make(name: 'avatar', directory: 'user/avatar')
                            ->avatar()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->columns()
                    ->columnSpan(2),
                Section::make(__('Information'))
                    ->schema([
                        DateTimePickerDisabled::make('created_at'),
                        DateTimePickerDisabled::make('updated_at'),
                    ])
                    ->collapsible(),
            ])
            ->columns(3);
    }
}
