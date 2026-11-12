<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Teams;

use Agenciafmd\Admix\Models\Team;
use Agenciafmd\Admix\Resources\Teams\Pages\CreateTeam;
use Agenciafmd\Admix\Resources\Teams\Pages\EditTeam;
use Agenciafmd\Admix\Resources\Teams\Pages\ListTeams;
use Agenciafmd\Admix\Resources\Teams\Schemas\TeamForm;
use Agenciafmd\Admix\Resources\Teams\Tables\TeamsTable;
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
 * Teams (tenants): only exists with the tenancy enabled and only administrators manage them.
 */
final class TeamResource extends Resource
{
    #[Override]
    protected static ?string $model = Team::class;

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
        return Team::isTenancyEnabled() && parent::canAccess();
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return __('Team');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return __('Teams');
    }

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return TeamForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return TeamsTable::configure($table);
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
            'index' => ListTeams::route('/'),
            'create' => CreateTeam::route('/create'),
            'edit' => EditTeam::route('/{record}/edit'),
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
