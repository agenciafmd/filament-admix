<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Providers;

use Agenciafmd\Admix\Commands\AdmixCreateUser;
use Agenciafmd\Admix\Commands\AuditPrune;
use Agenciafmd\Admix\Commands\NotificationsClear;
use Agenciafmd\Admix\Models\Role;
use Agenciafmd\Admix\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

final class CommandServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            AdmixCreateUser::class,
            AuditPrune::class,
            NotificationsClear::class,
        ]);

        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);
            $minutes = config()->string('filament-admix.schedule.minutes', '00');

            if (config()->boolean('filament-admix.queue.enabled') && (config()->string('queue.default') !== 'sync')) {
                $schedule->command('queue:work --tries=3 --delay=5 --timeout=60 --max-time=600 --queue=high,default,low')
                    ->name(now()->format('H:i') . ' Rotina de processamento de fila')
                    ->runInBackground()
                    ->withoutOverlapping(15)
                    ->everyMinute();
            }

            $schedule->command('auth:clear-resets')
                ->everyFifteenMinutes();
            $schedule->command('clockwork:clean')
                ->before(function (): void {
                    $storagePath = config('clockwork.storage_files_path');

                    if (! is_string($storagePath) || config('clockwork.storage') !== 'files') {
                        return;
                    }

                    $path = $storagePath . '/index';
                    if (! File::exists($path)) {
                        File::ensureDirectoryExists(dirname($path));
                        File::put($path, '');
                    }
                })
                ->withoutOverlapping()
                ->dailyAt("04:{$minutes}");
            $schedule->command('notifications:clear 90')
                ->withoutOverlapping()
                ->dailyAt("04:{$minutes}");
            $schedule->command('audit:prune')
                ->withoutOverlapping()
                ->dailyAt("03:{$minutes}");
            $schedule->command('model:prune', [
                '--model' => [
                    User::class,
                ],
            ])
                ->dailyAt("03:{$minutes}");
            $schedule->command('model:prune', [
                '--model' => [
                    Role::class,
                ],
            ])
                ->dailyAt("03:{$minutes}");
        });
    }
}
