<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Tests\Feature\Traits;

use Agenciafmd\Admix\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('keeps only the active records', function (): void {
    $activeRole = Role::factory()->create();
    Role::factory()->inactive()->create();

    expect(Role::query()->isActive()->pluck('id')->all())->toBe([$activeRole->getKey()]);
});

it('orders by the default sort of the model', function (): void {
    Role::factory()->inactive()->create(['name' => 'Alpha']);
    Role::factory()->create(['name' => 'Charlie']);
    Role::factory()->create(['name' => 'Bravo']);

    expect(Role::query()->sort()->pluck('name')->all())->toBe(['Bravo', 'Charlie', 'Alpha']);
});
