<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Tests\Feature\Http\Controllers;

use Agenciafmd\Admix\Models\Role;
use Agenciafmd\Admix\Models\User;
use Agenciafmd\Admix\Resources\Users\UserResource;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admix');
});

it('takes the administrator back to their own account', function (): void {
    $administrator = User::factory()->create();
    $member = User::factory()->create(['role_id' => Role::factory()]);

    actingAs($member, 'admix-web')
        ->withSession(['filament-admix.impersonator_id' => $administrator->getKey()])
        ->post(route('filament.admix.impersonation.leave'))
        ->assertRedirect(UserResource::getUrl('index'))
        ->assertSessionMissing('filament-admix.impersonator_id');

    expect(auth('admix-web')->id())->toBe($administrator->getKey());
});

it('logs out when the impersonator is no longer an administrator', function (): void {
    $formerAdministrator = User::factory()->create(['role_id' => Role::factory()]);
    $member = User::factory()->create(['role_id' => Role::factory()])->fresh();

    actingAs($member, 'admix-web')
        ->withSession(['filament-admix.impersonator_id' => $formerAdministrator->getKey()])
        ->post(route('filament.admix.impersonation.leave'))
        ->assertRedirect(Filament::getLoginUrl());

    expect(auth('admix-web')->guest())->toBeTrue();
});

it('forbids leaving without impersonating', function (): void {
    $member = User::factory()->create(['role_id' => Role::factory()]);

    actingAs($member, 'admix-web');

    post(route('filament.admix.impersonation.leave'))->assertForbidden();

    expect(auth('admix-web')->id())->toBe($member->getKey());
});
