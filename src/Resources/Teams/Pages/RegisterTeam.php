<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Teams\Pages;

use Agenciafmd\Admix\Resources\Teams\Schemas\TeamForm;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Override;

/**
 * Creates a team from the tenant menu. It is also where administrators land
 * while no team exists. Only administrators can create teams (`TeamResource::isAdministratorsOnly()`).
 */
final class RegisterTeam extends RegisterTenant
{
    #[Override]
    public static function getLabel(): string
    {
        return __('New team');
    }

    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(TeamForm::fields());
    }
}
