<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Tests\Feature\Resources;

use Agenciafmd\Admix\Resources\Roles\RoleResource;
use Agenciafmd\Admix\Resources\Users\UserResource;
use Tests\TestCase;

pest()->extend(TestCase::class);

it('reads the navigation group of users and roles from the config', function (): void {
    config()->set('filament-admix.navigation_group', 'Configurações');

    expect(UserResource::getNavigationGroup())->toBe('Configurações')
        ->and(RoleResource::getNavigationGroup())->toBe('Configurações');
});

it('has no navigation group when the config is not a string', function (): void {
    config()->set('filament-admix.navigation_group', null);

    expect(UserResource::getNavigationGroup())->toBeNull()
        ->and(RoleResource::getNavigationGroup())->toBeNull();
});
