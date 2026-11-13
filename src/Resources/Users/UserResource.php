<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Users;

use Agenciafmd\Admix\Models\Organization;
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
     * Users may belong to several organizations, so the organization scope is applied in `getEloquentQuery()`.
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
     * With the tenancy enabled, users with a role only see the users of the current organization.
     */
    #[Override]
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with('organizations');
        $organization = Filament::getTenant();

        if (! Organization::isTenancyEnabled() || ! $organization instanceof Organization || self::authenticatedUser()?->isAdmin()) {
            return $query;
        }

        return $query->whereRelation('organizations', $organization->getQualifiedKeyName(), $organization->getKey());
    }

    /**
     * Deletes the user. With the tenancy enabled, users with a role only remove the user
     * from the current organization, and the user is deleted when no organization is left.
     */
    public static function deleteRecord(User $user): bool
    {
        $organization = Filament::getTenant();

        if (! Organization::isTenancyEnabled() || ! $organization instanceof Organization || self::authenticatedUser()?->isAdmin()) {
            return (bool) $user->delete();
        }

        $user->organizations()->detach($organization);

        if ($user->organizations()->exists()) {
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
