<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Auth\Pages;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Component;
use Override;

final class Login extends BaseLogin
{
    #[Override]
    protected function getRememberFormComponent(): Component
    {
        /** @var Checkbox $component */
        $component = parent::getRememberFormComponent();

        return $component->default(true);
    }
}
