<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Tests\Feature\Forms;

use Agenciafmd\Admix\Models\User;
use Agenciafmd\Admix\Resources\Users\Pages\CreateUser;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

use function Pest\Laravel\actingAs;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    actingAs(User::factory()->create()->fresh(), 'admix-web');
    Filament::setCurrentPanel('admix');
});

it('resizes the image to integer dimensions and shows them in the label', function (): void {
    Livewire::test(CreateUser::class)
        ->assertFormFieldExists('avatar', static fn (FileUpload $field): bool => $field->getAutomaticallyResizeImagesWidth() === '500'
            && $field->getAutomaticallyResizeImagesHeight() === '500')
        ->assertSee('Max. 500x500');
});
