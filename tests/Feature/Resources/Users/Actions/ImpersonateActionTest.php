<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Tests\Feature\Resources\Users\Actions;

use Agenciafmd\Admix\Models\Role;
use Agenciafmd\Admix\Models\User;
use Agenciafmd\Admix\Permissions\PermissionRegistry;
use Agenciafmd\Admix\Resources\Users\Pages\ListUsers;
use Agenciafmd\Admix\Resources\Users\UserResource;
use Filament\Actions\Testing\TestAction;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

beforeEach(function (): void {
    config(['filament-admix.impersonate' => true]);
});

it('lets administrators access the panel as a user with a role', function (): void {
    $administrator = User::factory()->create()->fresh();
    $member = User::factory()->create(['role_id' => Role::factory()]);

    actingAs($administrator, 'admix-web');

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('impersonate')->table($member))
        ->assertRedirect(Dashboard::getUrl(panel: 'admix'));

    expect(auth('admix-web')->id())->toBe($member->getKey())
        ->and(User::isImpersonating())->toBeTrue();

    get(Dashboard::getUrl(panel: 'admix'))
        ->assertOk()
        ->assertSee(__('Back to my account'));
});

it('only shows the action for users the administrator can impersonate', function (): void {
    actingAs(User::factory()->create()->fresh(), 'admix-web');

    Livewire::test(ListUsers::class)
        ->assertActionHidden(TestAction::make('impersonate')->table(User::factory()->create()))
        ->assertActionHidden(TestAction::make('impersonate')->table(User::factory()->create(['role_id' => Role::factory(), 'is_active' => false])))
        ->assertActionVisible(TestAction::make('impersonate')->table(User::factory()->create(['role_id' => Role::factory()])));
});

it('hides the action from users that are not administrators', function (): void {
    $manager = User::factory()->create([
        'role_id' => Role::factory()->withPermissions(collect(['view', 'update'])
            ->map(static fn (string $ability): string => PermissionRegistry::permissionKey(UserResource::class, $ability))
            ->all()),
    ])->fresh();

    actingAs($manager, 'admix-web');

    Livewire::test(ListUsers::class)
        ->assertActionHidden(TestAction::make('impersonate')->table(User::factory()->create(['role_id' => Role::factory()])));
});

it('hides the action when the impersonate is disabled', function (): void {
    config(['filament-admix.impersonate' => false]);

    actingAs(User::factory()->create()->fresh(), 'admix-web');

    Livewire::test(ListUsers::class)
        ->assertActionHidden(TestAction::make('impersonate')->table(User::factory()->create(['role_id' => Role::factory()])));
});
