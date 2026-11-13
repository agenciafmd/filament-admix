<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Tests\Feature\Tenancy;

use Agenciafmd\Admix\Models\Organization;
use Agenciafmd\Admix\Models\Role;
use Agenciafmd\Admix\Models\User;
use Agenciafmd\Admix\Permissions\PermissionRegistry;
use Agenciafmd\Admix\Resources\Organizations\OrganizationResource;
use Agenciafmd\Admix\Resources\Organizations\Pages\CreateOrganization;
use Agenciafmd\Admix\Resources\Roles\RoleResource;
use Agenciafmd\Admix\Resources\Users\Pages\CreateUser;
use Agenciafmd\Admix\Resources\Users\Pages\EditUser;
use Agenciafmd\Admix\Resources\Users\Pages\ListUsers;
use Agenciafmd\Admix\Resources\Users\UserResource;
use Agenciafmd\Admix\Testing\WithTenancy;
use Agenciafmd\Admix\Traits\BelongsToOrganization;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Livewire\Sidebar;
use Filament\Pages\Dashboard;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

#[Unguarded]
final class OrganizationNote extends Model
{
    use BelongsToOrganization;
    use HasFactory;
}

pest()->extend(TestCase::class)->use(RefreshDatabase::class, WithTenancy::class);

/**
 * @param  array<int, string>|null  $permissions  null creates an administrator
 */
function actingAsOrganizationMember(?Organization $organization, ?array $permissions = []): User
{
    $user = User::factory()->create([
        'role_id' => $permissions === null ? null : Role::factory()->withPermissions($permissions)->create()->getKey(),
    ]);

    if ($organization instanceof Organization) {
        $user->organizations()->attach($organization);
    }

    actingAs($user->fresh(), 'admix-web');
    Filament::setCurrentPanel('admix');

    if ($organization instanceof Organization) {
        Filament::setTenant($organization);
    }

    return $user;
}

function userPermission(string $ability): string
{
    return PermissionRegistry::permissionKey(UserResource::class, $ability);
}

it('denies the panel to users with a role and without an active organization', function (): void {
    $organization = Organization::factory()->inactive()->create();

    actingAsOrganizationMember($organization);

    get(Dashboard::getUrl(panel: 'admix', tenant: $organization))->assertForbidden();
});

it('only lets users with a role access their own active organizations', function (): void {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $user = actingAsOrganizationMember($organization, [userPermission('view')]);

    expect($user->getTenants(Filament::getPanel('admix'))->modelKeys())->toBe([$organization->getKey()]);

    get(UserResource::getUrl('index', tenant: $organization))->assertOk();
    get(UserResource::getUrl('index', tenant: $otherOrganization))->assertNotFound();
});

it('lets administrators access every organization', function (): void {
    $organizations = Organization::factory()->count(2)->create();
    $administrator = actingAsOrganizationMember(null, null);

    expect($administrator->getTenants(Filament::getPanel('admix'))->modelKeys())->toEqualCanonicalizing($organizations->modelKeys());

    get(UserResource::getUrl('index', tenant: $organizations->last()))->assertOk();
});

it('applies the role permissions inside the organization', function (): void {
    $organization = Organization::factory()->create();

    actingAsOrganizationMember($organization);

    get(UserResource::getUrl('index', tenant: $organization))->assertForbidden();
});

it('lists only the users of the current organization for users with a role', function (): void {
    $organization = Organization::factory()->create();
    $member = User::factory()->create(['role_id' => Role::factory()]);
    $member->organizations()->attach($organization);
    $outsider = User::factory()->create(['role_id' => Role::factory()]);
    $outsider->organizations()->attach(Organization::factory()->create());

    $user = actingAsOrganizationMember($organization, [userPermission('view')]);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$user, $member])
        ->assertCanNotSeeTableRecords([$outsider]);
});

it('adds an existing e-mail to the organization instead of creating another user', function (): void {
    $organization = Organization::factory()->create();
    $role = Role::factory()->create();
    $existing = User::factory()->create(['role_id' => $role, 'name' => 'Original name', 'email' => 'existing@gmail.com']);
    $existing->organizations()->attach(Organization::factory()->create());

    actingAsOrganizationMember($organization, [userPermission('view'), userPermission('create')]);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Another name',
            'email' => $existing->email,
            'password' => 'Secret@123456',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::query()->where('email', $existing->email)->count())->toBe(1)
        ->and($existing->fresh()->name)->toBe('Original name')
        ->and($existing->organizations()->whereKey($organization->getKey())->exists())->toBeTrue();
});

it('adds new users to the current organization', function (): void {
    $organization = Organization::factory()->create();

    actingAsOrganizationMember($organization, [userPermission('view'), userPermission('create')]);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'New user',
            'email' => 'new-user@gmail.com',
            'password' => 'Secret@123456',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::query()->firstWhere('email', 'new-user@gmail.com')->organizations->modelKeys())->toBe([$organization->getKey()]);
});

it('only removes users shared with other organizations from the current organization', function (): void {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $shared = User::factory()->create(['role_id' => Role::factory()]);
    $shared->organizations()->attach([$organization->getKey(), $otherOrganization->getKey()]);

    actingAsOrganizationMember($organization, [userPermission('view'), userPermission('update'), userPermission('delete')]);

    $shared->load('organizations');

    expect(Gate::allows('update', $shared))->toBeFalse()
        ->and(Gate::allows('delete', $shared))->toBeTrue();

    Livewire::test(ListUsers::class)
        ->callTableBulkAction(DeleteBulkAction::class, [$shared]);

    expect($shared->fresh()->trashed())->toBeFalse()
        ->and($shared->organizations()->pluck('organizations.id')->all())->toBe([$otherOrganization->getKey()]);
});

it('deletes users removed from their last organization', function (): void {
    $organization = Organization::factory()->create();
    $member = User::factory()->create(['role_id' => Role::factory()]);
    $member->organizations()->attach($organization);

    actingAsOrganizationMember($organization, [userPermission('view'), userPermission('update'), userPermission('delete')]);

    Livewire::test(EditUser::class, ['record' => $member->getRouteKey()])
        ->callAction(DeleteAction::class);

    expect($member->fresh()->trashed())->toBeTrue();
});

it('keeps organizations and roles for administrators only', function (): void {
    $organization = Organization::factory()->create();

    actingAsOrganizationMember($organization, [
        PermissionRegistry::permissionKey(OrganizationResource::class, 'view'),
        PermissionRegistry::permissionKey(RoleResource::class, 'view'),
    ]);

    get(OrganizationResource::getUrl('index', tenant: $organization))->assertForbidden();
    get(RoleResource::getUrl('index', tenant: $organization))->assertForbidden();

    expect(collect(resolve(PermissionRegistry::class)->groups())->pluck('resource'))
        ->not->toContain(OrganizationResource::class, RoleResource::class);
});

it('lets administrators manage organizations', function (): void {
    $organization = Organization::factory()->create();

    actingAsOrganizationMember(null, null);

    get(OrganizationResource::getUrl('index', tenant: $organization))->assertOk();
    get(RoleResource::getUrl('index', tenant: $organization))->assertOk();
});

it('shows the name and the e-mail of the users in the organization form', function (): void {
    $organization = Organization::factory()->create();
    $member = User::factory()->create(['name' => 'Maria Souza', 'email' => 'maria@gmail.com']);

    actingAsOrganizationMember(null, null);
    Filament::setTenant($organization);

    Livewire::test(CreateOrganization::class)
        ->assertFormFieldExists('general.users', static fn (Select $field): bool => $field->getOptionLabelFromRecord($member) === 'Maria Souza (maria@gmail.com)');
});

it('creates organizations in a modal from the tenant menu', function (): void {
    $organization = Organization::factory()->create();
    $member = User::factory()->create();

    actingAsOrganizationMember(null, null);
    Filament::setTenant($organization);

    Livewire::test(Sidebar::class)
        ->mountAction('register')
        ->set('mountedActions.0.data', [
            'name' => 'Nova Organização',
            'slug' => 'nova-organizacao',
            'users' => [$member->getKey()],
        ])
        ->callMountedAction()
        ->assertRedirect(Filament::getUrl(Organization::query()->firstWhere('slug', 'nova-organizacao')));

    expect(Organization::query()->firstWhere('slug', 'nova-organizacao')->users->modelKeys())->toBe([$member->getKey()]);
});

it('shows the avatar of the organization in the tenant menu instead of the initials', function (): void {
    Storage::fake('public');

    $organization = Organization::factory()->create(['avatar' => 'organization/avatar/logo.jpg']);
    $withoutAvatar = Organization::factory()->create();

    actingAsOrganizationMember(null, null);

    expect(Filament::getTenantAvatarUrl($organization))->toBe(Storage::url('organization/avatar/logo.jpg'))
        ->and($withoutAvatar->getFilamentAvatarUrl())->toBeNull();
});

it('only accepts organization slugs that work as subdomains', function (string $slug, string $rule): void {
    actingAsOrganizationMember(null, null);
    Filament::setTenant(Organization::factory()->create());

    Livewire::test(CreateOrganization::class)
        ->fillForm(['name' => 'Organização', 'slug' => $slug])
        ->call('create')
        ->assertHasFormErrors(['slug' => $rule]);
})->with([
    'reserved' => ['admix', 'not_in'],
    'uppercase' => ['Organização', 'regex'],
    'underscore' => ['minha_organizacao', 'regex'],
]);

it('hides the new organization action from users that are not administrators', function (): void {
    actingAsOrganizationMember(Organization::factory()->create());

    Livewire::test(Sidebar::class)
        ->assertActionDoesNotExist('register');
});

it('scopes the models that belong to a organization and fills the current organization', function (): void {
    Schema::create('organization_notes', static function (Blueprint $table): void {
        $table->id();
        $table->foreignId('organization_id');
        $table->string('title');
        $table->timestamps();
    });

    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();

    actingAs(User::factory()->create(), 'admix-web');
    Filament::setTenant($otherOrganization);
    OrganizationNote::query()->create(['title' => 'Other']);

    Filament::setTenant($organization);
    $note = OrganizationNote::query()->create(['title' => 'Mine']);

    expect($note->organization_id)->toBe($organization->getKey())
        ->and(OrganizationNote::query()->pluck('title')->all())->toBe(['Mine']);

    config(['filament-admix.tenancy.enabled' => false]);

    expect(OrganizationNote::query()->count())->toBe(2);
});
