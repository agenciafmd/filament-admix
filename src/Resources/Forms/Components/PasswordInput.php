<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Forms\Components;

use Filament\Forms\Components\TextInput;
use Illuminate\Validation\Rules\Password;

final class PasswordInput
{
    public static function make(string $name = 'password'): TextInput
    {
        return TextInput::make($name)
            ->translateLabel()
            ->password()
            ->revealable()
            ->rule(Password::default())
            ->dehydrated(fn (?string $state): bool => filled($state))
            ->required(fn (string $operation): bool => $operation === 'create');
    }
}
