<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Tests\Feature\Resources\Users;

use Agenciafmd\Admix\Models\Role;
use Agenciafmd\Admix\Models\User;
use Agenciafmd\Admix\Permissions\PermissionRegistry;
use Agenciafmd\Admix\Resources\Users\Pages\CreateUser;
use Agenciafmd\Admix\Resources\Users\Pages\EditUser;
use Agenciafmd\Admix\Resources\Users\UserResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

use function Pest\Laravel\actingAs;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

function actingAsUserManager(): User
{
    $user = User::factory()->create([
        'role_id' => Role::factory()->withPermissions(collect(['view', 'create', 'update'])
            ->map(static fn (string $ability): string => PermissionRegistry::permissionKey(UserResource::class, $ability))
            ->all()),
    ])->fresh();

    actingAs($user, 'admix-web');

    return $user;
}

it('hides the role from users that are not administrators', function (): void {
    actingAsUserManager();

    $member = User::factory()->create(['role_id' => Role::factory()]);

    Livewire::test(CreateUser::class)
        ->assertFormFieldHidden('general.role_id');

    Livewire::test(EditUser::class, ['record' => $member->getRouteKey()])
        ->assertFormFieldHidden('general.role_id');
});

it('creates users with the role of who created them', function (): void {
    $creator = actingAsUserManager();

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'New user',
            'email' => 'new-user@gmail.com',
            'password' => 'Secret@123456',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::query()->firstWhere('email', 'new-user@gmail.com')->role_id)->toBe($creator->role_id);
});

it('keeps the role when users that are not administrators edit a user', function (): void {
    actingAsUserManager();

    $role = Role::factory()->create();
    $member = User::factory()->create(['role_id' => $role, 'email' => 'member@gmail.com']);

    Livewire::test(EditUser::class, ['record' => $member->getRouteKey()])
        ->fillForm(['name' => 'Renamed user'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($member->fresh())
        ->name->toBe('Renamed user')
        ->role_id->toBe($role->getKey());
});

it('lets administrators choose the role', function (): void {
    actingAs(User::factory()->create()->fresh(), 'admix-web');

    $role = Role::factory()->create();

    Livewire::test(CreateUser::class)
        ->assertFormFieldVisible('general.role_id')
        ->fillForm([
            'name' => 'New user',
            'email' => 'new-user@gmail.com',
            'password' => 'Secret@123456',
            'role_id' => $role->getKey(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::query()->firstWhere('email', 'new-user@gmail.com')->role_id)->toBe($role->getKey());
});
