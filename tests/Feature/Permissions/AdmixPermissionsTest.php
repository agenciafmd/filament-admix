<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Tests\Feature\Permissions;

use Agenciafmd\Admix\Models\Role;
use Agenciafmd\Admix\Models\User;
use Agenciafmd\Admix\Permissions\PermissionRegistry;
use Agenciafmd\Admix\Resources\Roles\Pages\CreateRole;
use Agenciafmd\Admix\Resources\Roles\RoleResource;
use Agenciafmd\Admix\Resources\Users\Pages\EditUser;
use Agenciafmd\Admix\Resources\Users\UserResource;
use Agenciafmd\Articles\Models\Article;
use Agenciafmd\Articles\Resources\Articles\ArticleResource;
use Agenciafmd\Articles\Resources\Articles\Pages\ListArticles;
use Agenciafmd\Postal\Models\Postal;
use Agenciafmd\Postal\Resources\Postal\Pages\ListPostal;
use Agenciafmd\Postal\Resources\Postal\PostalResource;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(TestCase::class, RefreshDatabase::class);

function articlePermission(string $ability): string
{
    return PermissionRegistry::permissionKey(ArticleResource::class, $ability);
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

    get(ArticleResource::getUrl('index'))->assertOk();
    get(ArticleResource::getUrl('create'))->assertOk();
    get(RoleResource::getUrl('index'))->assertOk();
});

it('denies resources the role has no permission for', function (): void {
    actingAsAdmixUser(Role::factory()->create());

    get(ArticleResource::getUrl('index'))->assertForbidden();
    get(ArticleResource::getUrl('create'))->assertForbidden();
    get(ArticleResource::getUrl('edit', ['record' => Article::factory()->create()]))->assertForbidden();

    expect(ArticleResource::canAccess())->toBeFalse();
});

it('grants only the abilities checked in the role', function (): void {
    actingAsAdmixUser(Role::factory()->withPermissions([articlePermission('view')])->create());

    get(ArticleResource::getUrl('index'))->assertOk();
    get(ArticleResource::getUrl('create'))->assertForbidden();

    Livewire::test(ListArticles::class)
        ->assertActionHidden(CreateAction::class);

    $article = Article::factory()->create();

    expect(Gate::allows('update', $article))->toBeFalse()
        ->and(Gate::allows('deleteAny', Article::class))->toBeFalse();
});

it('maps grouped abilities to every related policy method', function (): void {
    actingAsAdmixUser(Role::factory()->withPermissions([articlePermission('delete'), articlePermission('restore')])->create());

    $article = Article::factory()->create();

    expect(Gate::allows('delete', $article))->toBeTrue()
        ->and(Gate::allows('deleteAny', Article::class))->toBeTrue()
        ->and(Gate::allows('forceDelete', $article))->toBeTrue()
        ->and(Gate::allows('restoreAny', Article::class))->toBeTrue()
        ->and(Gate::allows('update', $article))->toBeFalse();
});

it('denies everything when the role is inactive or deleted', function (): void {
    $role = Role::factory()->inactive()->withPermissions([articlePermission('view')])->create();

    $user = actingAsAdmixUser($role);

    get(ArticleResource::getUrl('index'))->assertForbidden();

    $role->update(['is_active' => true]);
    $role->delete();

    expect($user->fresh()->isAdmin())->toBeFalse()
        ->and($user->fresh()->hasPermission(articlePermission('view')))->toBeFalse();
});

it('controls the audit gates with the audit ability', function (): void {
    actingAsAdmixUser(Role::factory()->withPermissions([articlePermission('audit')])->create());

    $article = Article::factory()->create();

    expect(Gate::allows('audit', $article))->toBeTrue()
        ->and(Gate::allows('restoreAudit', $article))->toBeFalse();
});

it('lists the permissions of every panel resource', function (): void {
    $groups = collect(resolve(PermissionRegistry::class)->groups())->keyBy('resource');

    expect($groups)->toHaveKeys([ArticleResource::class, UserResource::class, RoleResource::class])
        ->and($groups[ArticleResource::class]['permissions'])->toHaveKeys([
            articlePermission('view'),
            articlePermission('create'),
            articlePermission('update'),
            articlePermission('delete'),
            articlePermission('restore'),
            articlePermission('audit'),
        ]);
});

it('saves the checked permissions on the role', function (): void {
    actingAsAdmixUser();

    Livewire::test(CreateRole::class)
        ->fillForm([
            'name' => 'Editors',
            'is_active' => true,
            'permissions' => [
                articlePermission('view'),
                articlePermission('update'),
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Role::query()->firstWhere('name', 'Editors')->permissions)
        ->toBe([articlePermission('view'), articlePermission('update')]);
});

it('drops unknown permission keys when saving the role', function (): void {
    actingAsAdmixUser();

    Livewire::test(CreateRole::class)
        ->fillForm([
            'name' => 'Editors',
            'is_active' => true,
            'permissions' => [articlePermission('view'), 'Unknown\\Resource@view'],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Role::query()->firstWhere('name', 'Editors')->permissions)
        ->toBe([articlePermission('view')]);
});

it('renders the permission matrix on the role form', function (): void {
    actingAsAdmixUser();

    get(RoleResource::getUrl('create'))
        ->assertOk()
        ->assertSee(ArticleResource::getPluralModelLabel())
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
        ->assertFormFieldDisabled('role_id');
});

it('checks the extra abilities declared by the resource', function (): void {
    $postal = Postal::factory()->create();

    actingAsAdmixUser(Role::factory()->withPermissions([
        PermissionRegistry::permissionKey(PostalResource::class, 'view'),
    ])->create());

    Livewire::test(ListPostal::class)
        ->assertTableActionHidden('send', $postal);

    actingAsAdmixUser(Role::factory()->withPermissions([
        PermissionRegistry::permissionKey(PostalResource::class, 'view'),
        PermissionRegistry::permissionKey(PostalResource::class, 'send'),
    ])->create());

    Livewire::test(ListPostal::class)
        ->assertTableActionVisible('send', $postal);
});
