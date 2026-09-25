<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Roles;

use Agenciafmd\Admix\Models\Role;
use Agenciafmd\Admix\Resources\Roles\Pages\CreateRole;
use Agenciafmd\Admix\Resources\Roles\Pages\EditRole;
use Agenciafmd\Admix\Resources\Roles\Pages\ListRoles;
use Agenciafmd\Admix\Resources\Roles\Schemas\RoleForm;
use Agenciafmd\Admix\Resources\Roles\Tables\RolesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Override;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

final class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function getModelLabel(): string
    {
        return __('Role');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return __('Roles');
    }

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
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
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
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
