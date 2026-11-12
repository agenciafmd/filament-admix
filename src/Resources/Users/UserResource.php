<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Users;

use Agenciafmd\Admix\Models\Team;
use Agenciafmd\Admix\Models\User;
use Agenciafmd\Admix\Resources\Users\Pages\CreateUser;
use Agenciafmd\Admix\Resources\Users\Pages\EditUser;
use Agenciafmd\Admix\Resources\Users\Pages\ListUsers;
use Agenciafmd\Admix\Resources\Users\Schemas\UserForm;
use Agenciafmd\Admix\Resources\Users\Tables\UsersTable;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Override;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

final class UserResource extends Resource
{
    #[Override]
    protected static ?string $model = User::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    #[Override]
    protected static ?int $navigationSort = 1;

    #[Override]
    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Users may belong to several teams, so the team scope is applied in `getEloquentQuery()`.
     */
    #[Override]
    protected static bool $isScopedToTenant = false;

    public static function getModelLabel(): string
    {
        return __('User');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Users');
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    /**
     * With the tenancy enabled, users with a role only see the users of the current team.
     */
    #[Override]
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with('teams');
        $team = Filament::getTenant();

        if (! Team::isTenancyEnabled() || ! $team instanceof Team || self::authenticatedUser()?->isAdmin()) {
            return $query;
        }

        return $query->whereRelation('teams', $team->getQualifiedKeyName(), $team->getKey());
    }

    /**
     * Deletes the user. With the tenancy enabled, users with a role only remove the user
     * from the current team, and the user is deleted when no team is left.
     */
    public static function deleteRecord(User $user): bool
    {
        $team = Filament::getTenant();

        if (! Team::isTenancyEnabled() || ! $team instanceof Team || self::authenticatedUser()?->isAdmin()) {
            return (bool) $user->delete();
        }

        $user->teams()->detach($team);

        if ($user->teams()->exists()) {
            return true;
        }

        return (bool) $user->delete();
    }

    #[Override]
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    private static function authenticatedUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
