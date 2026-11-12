<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Teams\Pages;

use Agenciafmd\Admix\Models\Team;
use Agenciafmd\Admix\Resources\Teams\Schemas\TeamForm;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Override;

/**
 * Creates a team. The tenant menu opens it in a modal (`modalAction()`); the page is where
 * administrators land while no team exists. Only administrators can create teams (`TeamResource::isAdministratorsOnly()`).
 */
final class RegisterTeam extends RegisterTenant
{
    #[Override]
    public static function getLabel(): string
    {
        return __('New team');
    }

    /**
     * The "New team" item of the tenant menu, opened in a modal instead of the page.
     */
    public static function modalAction(Action $action): CreateAction
    {
        return CreateAction::make($action->getName())
            ->label($action->getLabel())
            ->icon($action->getIcon())
            ->sort($action->getSort())
            ->model(Team::class)
            ->modalHeading(self::getLabel())
            ->schema(TeamForm::fields())
            ->visible(static fn (): bool => self::canView())
            ->successRedirectUrl(static fn (Team $record): ?string => Filament::getUrl($record));
    }

    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(TeamForm::fields());
    }
}
