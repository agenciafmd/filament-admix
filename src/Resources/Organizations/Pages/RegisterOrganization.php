<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Organizations\Pages;

use Agenciafmd\Admix\Models\Organization;
use Agenciafmd\Admix\Resources\Organizations\Schemas\OrganizationForm;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Override;

/**
 * Creates a organization. The tenant menu opens it in a modal (`modalAction()`); the page is where
 * administrators land while no organization exists. Only administrators can create organizations (`OrganizationResource::isAdministratorsOnly()`).
 */
final class RegisterOrganization extends RegisterTenant
{
    #[Override]
    public static function getLabel(): string
    {
        return __('New organization');
    }

    /**
     * The "New organization" item of the tenant menu, opened in a modal instead of the page.
     */
    public static function modalAction(Action $action): CreateAction
    {
        return CreateAction::make($action->getName())
            ->label($action->getLabel())
            ->icon($action->getIcon())
            ->sort($action->getSort())
            ->model(Organization::class)
            ->modalHeading(self::getLabel())
            ->schema(OrganizationForm::fields())
            ->visible(static fn (): bool => self::canView())
            ->successRedirectUrl(static fn (Organization $record): ?string => Filament::getUrl($record));
    }

    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(OrganizationForm::fields());
    }
}
