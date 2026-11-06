<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Tests\Feature\Commands;

use Agenciafmd\Admix\Providers\CommandServiceProvider;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

pest()->extend(TestCase::class);

/**
 * @return list<Event>
 */
function sessionGarbageCollectEvents(string $driver): array
{
    config()->set('session.driver', $driver);
    app()->instance(Schedule::class, new Schedule);

    new CommandServiceProvider(app())->boot();

    return collect(resolve(Schedule::class)->events())
        ->filter(fn (Event $event): bool => str($event->command ?? '')->contains('session:garbage-collect'))
        ->values()
        ->all();
}

it('schedules the session garbage collector hourly for the file and database drivers', function (string $driver): void {
    $events = sessionGarbageCollectEvents($driver);

    expect($events)->toHaveCount(1)
        ->and($events[0]->expression)->toBe('0 * * * *');
})->with(['file', 'database']);

it('does not schedule the session garbage collector for other drivers', function (string $driver): void {
    expect(sessionGarbageCollectEvents($driver))->toBeEmpty();
})->with(['redis', 'cookie', 'array']);
