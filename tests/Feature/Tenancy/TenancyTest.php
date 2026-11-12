<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Tests\Feature\Tenancy;

use Agenciafmd\Admix\Models\Role;
use Agenciafmd\Admix\Models\Team;
use Agenciafmd\Admix\Models\User;
use Agenciafmd\Admix\Permissions\PermissionRegistry;
use Agenciafmd\Admix\Resources\Roles\RoleResource;
use Agenciafmd\Admix\Resources\Teams\Pages\CreateTeam;
use Agenciafmd\Admix\Resources\Teams\TeamResource;
use Agenciafmd\Admix\Resources\Users\Pages\CreateUser;
use Agenciafmd\Admix\Resources\Users\Pages\EditUser;
use Agenciafmd\Admix\Resources\Users\Pages\ListUsers;
use Agenciafmd\Admix\Resources\Users\UserResource;
use Agenciafmd\Admix\Testing\WithTenancy;
use Agenciafmd\Admix\Traits\BelongsToTeam;
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
final class TeamNote extends Model
{
    use BelongsToTeam;
    use HasFactory;
}

pest()->extend(TestCase::class)->use(RefreshDatabase::class, WithTenancy::class);

/**
 * @param  array<int, string>|null  $permissions  null creates an administrator
 */
function actingAsTeamMember(?Team $team, ?array $permissions = []): User
{
    $user = User::factory()->create([
        'role_id' => $permissions === null ? null : Role::factory()->withPermissions($permissions)->create()->getKey(),
    ]);

    if ($team instanceof Team) {
        $user->teams()->attach($team);
    }

    actingAs($user->fresh(), 'admix-web');
    Filament::setCurrentPanel('admix');

    if ($team instanceof Team) {
        Filament::setTenant($team);
    }

    return $user;
}

function userPermission(string $ability): string
{
    return PermissionRegistry::permissionKey(UserResource::class, $ability);
}

it('denies the panel to users with a role and without an active team', function (): void {
    $team = Team::factory()->inactive()->create();

    actingAsTeamMember($team);

    get(Dashboard::getUrl(panel: 'admix', tenant: $team))->assertForbidden();
});

it('only lets users with a role access their own active teams', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $user = actingAsTeamMember($team, [userPermission('view')]);

    expect($user->getTenants(Filament::getPanel('admix'))->modelKeys())->toBe([$team->getKey()]);

    get(UserResource::getUrl('index', tenant: $team))->assertOk();
    get(UserResource::getUrl('index', tenant: $otherTeam))->assertNotFound();
});

it('lets administrators access every team', function (): void {
    $teams = Team::factory()->count(2)->create();
    $administrator = actingAsTeamMember(null, null);

    expect($administrator->getTenants(Filament::getPanel('admix'))->modelKeys())->toEqualCanonicalizing($teams->modelKeys());

    get(UserResource::getUrl('index', tenant: $teams->last()))->assertOk();
});

it('applies the role permissions inside the team', function (): void {
    $team = Team::factory()->create();

    actingAsTeamMember($team);

    get(UserResource::getUrl('index', tenant: $team))->assertForbidden();
});

it('lists only the users of the current team for users with a role', function (): void {
    $team = Team::factory()->create();
    $member = User::factory()->create(['role_id' => Role::factory()]);
    $member->teams()->attach($team);
    $outsider = User::factory()->create(['role_id' => Role::factory()]);
    $outsider->teams()->attach(Team::factory()->create());

    $user = actingAsTeamMember($team, [userPermission('view')]);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$user, $member])
        ->assertCanNotSeeTableRecords([$outsider]);
});

it('adds an existing e-mail to the team instead of creating another user', function (): void {
    $team = Team::factory()->create();
    $role = Role::factory()->create();
    $existing = User::factory()->create(['role_id' => $role, 'name' => 'Original name', 'email' => 'existing@gmail.com']);
    $existing->teams()->attach(Team::factory()->create());

    actingAsTeamMember($team, [userPermission('view'), userPermission('create')]);

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
        ->and($existing->teams()->whereKey($team->getKey())->exists())->toBeTrue();
});

it('adds new users to the current team', function (): void {
    $team = Team::factory()->create();

    actingAsTeamMember($team, [userPermission('view'), userPermission('create')]);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'New user',
            'email' => 'new-user@gmail.com',
            'password' => 'Secret@123456',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::query()->firstWhere('email', 'new-user@gmail.com')->teams->modelKeys())->toBe([$team->getKey()]);
});

it('only removes users shared with other teams from the current team', function (): void {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $shared = User::factory()->create(['role_id' => Role::factory()]);
    $shared->teams()->attach([$team->getKey(), $otherTeam->getKey()]);

    actingAsTeamMember($team, [userPermission('view'), userPermission('update'), userPermission('delete')]);

    $shared->load('teams');

    expect(Gate::allows('update', $shared))->toBeFalse()
        ->and(Gate::allows('delete', $shared))->toBeTrue();

    Livewire::test(ListUsers::class)
        ->callTableBulkAction(DeleteBulkAction::class, [$shared]);

    expect($shared->fresh()->trashed())->toBeFalse()
        ->and($shared->teams()->pluck('teams.id')->all())->toBe([$otherTeam->getKey()]);
});

it('deletes users removed from their last team', function (): void {
    $team = Team::factory()->create();
    $member = User::factory()->create(['role_id' => Role::factory()]);
    $member->teams()->attach($team);

    actingAsTeamMember($team, [userPermission('view'), userPermission('update'), userPermission('delete')]);

    Livewire::test(EditUser::class, ['record' => $member->getRouteKey()])
        ->callAction(DeleteAction::class);

    expect($member->fresh()->trashed())->toBeTrue();
});

it('keeps teams and roles for administrators only', function (): void {
    $team = Team::factory()->create();

    actingAsTeamMember($team, [
        PermissionRegistry::permissionKey(TeamResource::class, 'view'),
        PermissionRegistry::permissionKey(RoleResource::class, 'view'),
    ]);

    get(TeamResource::getUrl('index', tenant: $team))->assertForbidden();
    get(RoleResource::getUrl('index', tenant: $team))->assertForbidden();

    expect(collect(resolve(PermissionRegistry::class)->groups())->pluck('resource'))
        ->not->toContain(TeamResource::class, RoleResource::class);
});

it('lets administrators manage teams', function (): void {
    $team = Team::factory()->create();

    actingAsTeamMember(null, null);

    get(TeamResource::getUrl('index', tenant: $team))->assertOk();
    get(RoleResource::getUrl('index', tenant: $team))->assertOk();
});

it('shows the name and the e-mail of the users in the team form', function (): void {
    $team = Team::factory()->create();
    $member = User::factory()->create(['name' => 'Maria Souza', 'email' => 'maria@gmail.com']);

    actingAsTeamMember(null, null);
    Filament::setTenant($team);

    Livewire::test(CreateTeam::class)
        ->assertFormFieldExists('general.users', static fn (Select $field): bool => $field->getOptionLabelFromRecord($member) === 'Maria Souza (maria@gmail.com)');
});

it('creates teams in a modal from the tenant menu', function (): void {
    $team = Team::factory()->create();
    $member = User::factory()->create();

    actingAsTeamMember(null, null);
    Filament::setTenant($team);

    Livewire::test(Sidebar::class)
        ->mountAction('register')
        ->set('mountedActions.0.data', [
            'name' => 'Nova Equipe',
            'slug' => 'nova-equipe',
            'users' => [$member->getKey()],
        ])
        ->callMountedAction()
        ->assertRedirect(Filament::getUrl(Team::query()->firstWhere('slug', 'nova-equipe')));

    expect(Team::query()->firstWhere('slug', 'nova-equipe')->users->modelKeys())->toBe([$member->getKey()]);
});

it('shows the avatar of the team in the tenant menu instead of the initials', function (): void {
    Storage::fake('public');

    $team = Team::factory()->create(['avatar' => 'team/avatar/logo.jpg']);
    $withoutAvatar = Team::factory()->create();

    actingAsTeamMember(null, null);

    expect(Filament::getTenantAvatarUrl($team))->toBe(Storage::url('team/avatar/logo.jpg'))
        ->and($withoutAvatar->getFilamentAvatarUrl())->toBeNull();
});

it('hides the new team action from users that are not administrators', function (): void {
    actingAsTeamMember(Team::factory()->create());

    Livewire::test(Sidebar::class)
        ->assertActionDoesNotExist('register');
});

it('scopes the models that belong to a team and fills the current team', function (): void {
    Schema::create('team_notes', static function (Blueprint $table): void {
        $table->id();
        $table->foreignId('team_id');
        $table->string('title');
        $table->timestamps();
    });

    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();

    actingAs(User::factory()->create(), 'admix-web');
    Filament::setTenant($otherTeam);
    TeamNote::query()->create(['title' => 'Other']);

    Filament::setTenant($team);
    $note = TeamNote::query()->create(['title' => 'Mine']);

    expect($note->team_id)->toBe($team->getKey())
        ->and(TeamNote::query()->pluck('title')->all())->toBe(['Mine']);

    config(['filament-admix.tenancy.enabled' => false]);

    expect(TeamNote::query()->count())->toBe(2);
});
