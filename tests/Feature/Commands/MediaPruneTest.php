<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Tests\Feature\Commands;

use Agenciafmd\Admix\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

use function Pest\Laravel\artisan;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('filament.default_filesystem_disk', 'public');
    Storage::fake('public');

    User::factory()->create(['avatar' => 'media/user/avatar/2026/10/20/linked-081639-999.jpg']);

    collect([
        'media/user/avatar/2026/10/20/linked-081639-999.jpg',
        'media/user/avatar/2026/10/20/orphan-081640-111.jpg',
        'media/unknown/image/2026/10/20/without-table.jpg',
        'media/user/unknown/2026/10/20/without-column.jpg',
        'media/rich-editor/user/avatar/2026/10/20/rich-editor.jpg',
    ])->each(static fn (string $file): bool => Storage::disk('public')->put($file, 'content'));
});

it('removes only the files that are not linked to the field of the model', function (): void {
    artisan('media:prune')
        ->expectsOutputToContain('1 arquivo(s) removidos com sucesso.')
        ->assertSuccessful();

    Storage::disk('public')->assertMissing('media/user/avatar/2026/10/20/orphan-081640-111.jpg');
    Storage::disk('public')->assertExists([
        'media/user/avatar/2026/10/20/linked-081639-999.jpg',
        'media/unknown/image/2026/10/20/without-table.jpg',
        'media/user/unknown/2026/10/20/without-column.jpg',
        'media/rich-editor/user/avatar/2026/10/20/rich-editor.jpg',
    ]);
});

it('only lists the orphan files on dry run', function (): void {
    artisan('media:prune', ['--dry-run' => true])
        ->expectsOutputToContain('1 arquivo(s) seriam removidos.')
        ->assertSuccessful();

    Storage::disk('public')->assertExists('media/user/avatar/2026/10/20/orphan-081640-111.jpg');
});
