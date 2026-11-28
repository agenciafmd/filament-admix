<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Tests\Feature\Permissions;

use Agenciafmd\Admix\Models\Role;
use Agenciafmd\Admix\Models\User;
use Agenciafmd\Admix\Permissions\PermissionRegistry;
use Agenciafmd\Admix\Resources\Roles\Pages\CreateRole;
use Agenciafmd\Admix\Resources\Roles\Pages\ListRoles;
use Agenciafmd\Admix\Resources\Roles\RoleResource;
use Agenciafmd\Admix\Resources\Users\Pages\EditUser;
use Agenciafmd\Admix\Resources\Users\UserResource;
use Agenciafmd\Postal\Models\Postal;
use Agenciafmd\Postal\Resources\Postal\Pages\ListPostal;
use Agenciafmd\Postal\Resources\Postal\PostalResource;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

function rolePermission(string $ability): string
{
    return PermissionRegistry::permissionKey(RoleResource::class, $ability);
}

function actingAsAdmixUser(?Role $role = null): User
{
    $user = User::factory()->create([
        'role_id' => $role?->getKey(),
    ])->fresh();

    actingAs($user, 'admix-web');
    Filament::setCurrentPanel('admix');

    return $user;
}

it('lets users without a role access every resource', function (): void {
    actingAsAdmixUser();

    get(RoleResource::getUrl('index'))->assertOk();
    get(RoleResource::getUrl('create'))->assertOk();
    get(UserResource::getUrl('index'))->assertOk();
});

it('denies resources the role has no permission for', function (): void {
    actingAsAdmixUser(Role::factory()->create());

    get(RoleResource::getUrl('index'))->assertForbidden();
    get(RoleResource::getUrl('create'))->assertForbidden();
    get(RoleResource::getUrl('edit', ['record' => Role::factory()->create()]))->assertForbidden();

    expect(RoleResource::canAccess())->toBeFalse();
});

it('grants only the abilities checked in the role', function (): void {
    actingAsAdmixUser(Role::factory()->withPermissions([rolePermission('view')])->create());

    get(RoleResource::getUrl('index'))->assertOk();
    get(RoleResource::getUrl('create'))->assertForbidden();

    Livewire::test(ListRoles::class)
        ->assertActionHidden(CreateAction::class);

    $managedRole = Role::factory()->create();

    expect(Gate::allows('update', $managedRole))->toBeFalse()
        ->and(Gate::allows('deleteAny', Role::class))->toBeFalse();
});

it('maps grouped abilities to every related policy method', function (): void {
    actingAsAdmixUser(Role::factory()->withPermissions([rolePermission('delete'), rolePermission('restore')])->create());

    $managedRole = Role::factory()->create();

    expect(Gate::allows('delete', $managedRole))->toBeTrue()
        ->and(Gate::allows('deleteAny', Role::class))->toBeTrue()
        ->and(Gate::allows('forceDelete', $managedRole))->toBeTrue()
        ->and(Gate::allows('restoreAny', Role::class))->toBeTrue()
        ->and(Gate::allows('update', $managedRole))->toBeFalse();
});

it('denies everything when the role is inactive or deleted', function (): void {
    $role = Role::factory()->inactive()->withPermissions([rolePermission('view')])->create();

    $user = actingAsAdmixUser($role);

    get(RoleResource::getUrl('index'))->assertForbidden();

    $role->update(['is_active' => true]);
    $role->delete();

    expect($user->fresh()->isAdmin())->toBeFalse()
        ->and($user->fresh()->hasPermission(rolePermission('view')))->toBeFalse();
});

it('controls the audit gates with the audit ability', function (): void {
    actingAsAdmixUser(Role::factory()->withPermissions([rolePermission('audit')])->create());

    $managedRole = Role::factory()->create();

    expect(Gate::allows('audit', $managedRole))->toBeTrue()
        ->and(Gate::allows('restoreAudit', $managedRole))->toBeFalse();
});

it('lists the permissions of every panel resource', function (): void {
    $groups = collect(resolve(PermissionRegistry::class)->groups())->keyBy('resource');

    expect($groups)->toHaveKeys([RoleResource::class, UserResource::class])
        ->and($groups[RoleResource::class]['permissions'])->toHaveKeys([
            rolePermission('view'),
            rolePermission('create'),
            rolePermission('update'),
            rolePermission('delete'),
            rolePermission('restore'),
            rolePermission('audit'),
        ]);
});

it('saves the checked permissions on the role', function (): void {
    actingAsAdmixUser();

    Livewire::test(CreateRole::class)
        ->fillForm([
            'name' => 'Editors',
            'is_active' => true,
            'permissions' => [
                rolePermission('view'),
                rolePermission('update'),
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Role::query()->firstWhere('name', 'Editors')->permissions)
        ->toBe([rolePermission('view'), rolePermission('update')]);
});

it('drops unknown permission keys when saving the role', function (): void {
    actingAsAdmixUser();

    Livewire::test(CreateRole::class)
        ->fillForm([
            'name' => 'Editors',
            'is_active' => true,
            'permissions' => [rolePermission('view'), 'Unknown\\Resource@view'],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Role::query()->firstWhere('name', 'Editors')->permissions)
        ->toBe([rolePermission('view')]);
});

it('renders the permission matrix on the role form', function (): void {
    actingAsAdmixUser();

    Livewire::test(CreateRole::class)
        ->call('loadLazySchema', 'form.permissions')
        ->call('$refresh')
        ->assertSee(RoleResource::getPluralModelLabel())
        ->assertSee(__('send'));
});

it('prevents non administrators from changing administrators', function (): void {
    actingAsAdmixUser(Role::factory()->withPermissions([
        PermissionRegistry::permissionKey(UserResource::class, 'view'),
        PermissionRegistry::permissionKey(UserResource::class, 'update'),
    ])->create());

    $administrator = User::factory()->create();

    expect(Gate::allows('view', $administrator))->toBeTrue()
        ->and(Gate::allows('update', $administrator))->toBeFalse();

    get(UserResource::getUrl('edit', ['record' => $administrator]))->assertForbidden();
});

it('does not let users change their own role', function (): void {
    $user = actingAsAdmixUser();

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->assertFormFieldDisabled('general.role_id');
});

it('checks the extra abilities declared by the resource', function (): void {
    $postal = Postal::factory()->create();

    actingAsAdmixUser(Role::factory()->withPermissions([
        PermissionRegistry::permissionKey(PostalResource::class, 'view'),
    ])->create());

    Livewire::test(ListPostal::class)
        ->assertActionHidden(TestAction::make('send')->table($postal));

    actingAsAdmixUser(Role::factory()->withPermissions([
        PermissionRegistry::permissionKey(PostalResource::class, 'view'),
        PermissionRegistry::permissionKey(PostalResource::class, 'send'),
    ])->create());

    Livewire::test(ListPostal::class)
        ->assertActionVisible(TestAction::make('send')->table($postal));
});
