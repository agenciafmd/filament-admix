<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Organizations;

use Agenciafmd\Admix\Models\Organization;
use Agenciafmd\Admix\Resources\Organizations\Pages\CreateOrganization;
use Agenciafmd\Admix\Resources\Organizations\Pages\EditOrganization;
use Agenciafmd\Admix\Resources\Organizations\Pages\ListOrganizations;
use Agenciafmd\Admix\Resources\Organizations\Schemas\OrganizationForm;
use Agenciafmd\Admix\Resources\Organizations\Tables\OrganizationsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Override;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

/**
 * Organizations (tenants): only exists with the tenancy enabled and only administrators manage them.
 */
final class OrganizationResource extends Resource
{
    #[Override]
    protected static ?string $model = Organization::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    #[Override]
    protected static ?int $navigationSort = 3;

    #[Override]
    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    protected static bool $isScopedToTenant = false;

    public static function isAdministratorsOnly(): bool
    {
        return true;
    }

    #[Override]
    public static function canAccess(): bool
    {
        return Organization::isTenancyEnabled() && parent::canAccess();
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return __('Organization');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return __('Organizations');
    }

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return OrganizationForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return OrganizationsTable::configure($table);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            AuditsRelationManager::class,
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListOrganizations::route('/'),
            'create' => CreateOrganization::route('/create'),
            'edit' => EditOrganization::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
