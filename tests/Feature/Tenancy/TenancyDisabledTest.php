<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Tests\Feature\Tenancy;

use Agenciafmd\Admix\Models\User;
use Agenciafmd\Admix\Permissions\PermissionRegistry;
use Agenciafmd\Admix\Resources\Roles\RoleResource;
use Agenciafmd\Admix\Resources\Teams\TeamResource;
use Agenciafmd\Admix\Resources\Users\UserResource;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

beforeEach(function (): void {
    actingAs(User::factory()->create()->fresh(), 'admix-web');
    Filament::setCurrentPanel('admix');
});

it('hides the teams while the tenancy is disabled', function (): void {
    get(UserResource::getUrl('index'))->assertOk();
    get(TeamResource::getUrl('index'))->assertForbidden();

    expect(TeamResource::canAccess())->toBeFalse();
});

it('keeps the roles in the permissions while the tenancy is disabled', function (): void {
    expect(collect(resolve(PermissionRegistry::class)->groups())->pluck('resource'))
        ->toContain(RoleResource::class, UserResource::class)
        ->not->toContain(TeamResource::class);
});
