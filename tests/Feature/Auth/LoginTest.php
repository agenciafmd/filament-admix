<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Tests\Feature\Auth;

use Agenciafmd\Admix\Resources\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admix');
});

it('checks remember me by default', function (): void {
    Livewire::test(Login::class)
        ->assertSchemaStateSet([
            'remember' => true,
        ]);
});
