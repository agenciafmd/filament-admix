<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Tests\Feature\Http\Middleware;

use Agenciafmd\Admix\Models\Role;
use Agenciafmd\Admix\Models\User;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\get;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    app()->detectEnvironment(fn (): string => 'local');
});

it('logs in as the configured user on the local environment', function (): void {
    $user = User::factory()->create([
        'role_id' => Role::factory(),
    ]);

    config(['filament-admix.auto_login' => $user->email]);

    get(Dashboard::getUrl(panel: 'admix'))->assertOk();

    expect(auth('admix-web')->id())->toBe($user->getKey());
});

it('logs in as the first active administrator when enabled with true', function (): void {
    User::factory()->create([
        'is_active' => false,
    ]);
    User::factory()->create([
        'role_id' => Role::factory(),
    ]);
    $administrator = User::factory()->create();

    config(['filament-admix.auto_login' => true]);

    get(Dashboard::getUrl(panel: 'admix'))->assertOk();

    expect(auth('admix-web')->id())->toBe($administrator->getKey());
});

it('does not log in outside the local environment', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    $user = User::factory()->create();

    config(['filament-admix.auto_login' => $user->email]);

    get(Dashboard::getUrl(panel: 'admix'))->assertRedirect();

    expect(auth('admix-web')->guest())->toBeTrue();
});

it('does not log in when disabled or when the user is inactive', function (mixed $autoLogin): void {
    User::factory()->create([
        'email' => 'inactive@fmd.ag',
        'is_active' => false,
    ]);

    config(['filament-admix.auto_login' => $autoLogin]);

    get(Dashboard::getUrl(panel: 'admix'))->assertRedirect();

    expect(auth('admix-web')->guest())->toBeTrue();
})->with([
    'disabled' => false,
    'empty' => null,
    'inactive user' => 'inactive@fmd.ag',
]);
