<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Tests\Feature\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

use function Pest\Laravel\artisan;
use function Pest\Laravel\travelTo;

uses(TestCase::class, RefreshDatabase::class);

/**
 * @return array<string, string|int|null>
 */
function notificationCreatedAt(string $createdAt): array
{
    return [
        'id' => Str::uuid()->toString(),
        'type' => 'Tests\\Notification',
        'notifiable_type' => 'Tests\\User',
        'notifiable_id' => 1,
        'data' => '{}',
        'read_at' => null,
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ];
}

it('deletes the notifications older than the given days', function (): void {
    travelTo('2026-09-25 12:00:00');
    DB::table('notifications')->insert([
        notificationCreatedAt('2026-09-10 12:00:00'),
        notificationCreatedAt('2026-09-24 12:00:00'),
    ]);

    artisan('notifications:clear', ['days' => 7])->assertSuccessful();

    expect(DB::table('notifications')->pluck('created_at')->all())->toBe(['2026-09-24 12:00:00']);
});

it('rejects a number of days that is not numeric', function (): void {
    travelTo('2026-09-25 12:00:00');
    DB::table('notifications')->insert(notificationCreatedAt('2026-09-10 12:00:00'));

    artisan('notifications:clear', ['days' => 'abc'])->assertFailed();

    expect(DB::table('notifications')->count())->toBe(1);
});
