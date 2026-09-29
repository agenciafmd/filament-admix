<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Tests\Feature\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

use function Pest\Laravel\artisan;
use function Pest\Laravel\travelTo;

uses(TestCase::class, RefreshDatabase::class);

/**
 * @return array<string, string|int>
 */
function auditCreatedAt(string $createdAt): array
{
    return [
        'event' => 'updated',
        'auditable_type' => 'Tests\\Model',
        'auditable_id' => 1,
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ];
}

it('deletes the audits older than the given days and report', function (): void {
    travelTo('2026-09-25 12:00:00');
    DB::table('audits')->insert([
        auditCreatedAt('2026-01-01 12:00:00'),
        auditCreatedAt('2026-09-20 12:00:00'),
    ]);

    artisan('audit:prune', ['days' => 180])
        ->expectsOutputToContain('Registros removidos com sucesso.')
        ->assertSuccessful();

    expect(DB::table('audits')->pluck('created_at')->all())->toBe(['2026-09-20 12:00:00']);
});
