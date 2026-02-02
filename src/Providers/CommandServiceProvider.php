<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Providers;

use Agenciafmd\Admix\Commands\AdmixCreateUser;
use Agenciafmd\Admix\Commands\NotificationsClear;
use Agenciafmd\Admix\Models\User;
use Illuminate\Console\Scheduling\Schedule;
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
            NotificationsClear::class,
        ]);

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $minutes = config('filament-admix.schedule.minutes');

            $schedule->command('auth:clear-resets')
                ->everyFifteenMinutes();
            $schedule->command('notifications:clear 90')
                ->withoutOverlapping()
                ->dailyAt("04:{$minutes}");
            //            $schedule->command('model:prune', [
            //                '--model' => [
            //                    Audit::class,
            //                ],
            //            ])->dailyAt("03:{$minutes}");
            //            $schedule->command('model:prune', [
            //                '--model' => [
            //                    Role::class,
            //                ],
            //            ])->dailyAt("03:{$minutes}");
            $schedule->command('model:prune', [
                '--model' => [
                    User::class,
                ],
            ])->dailyAt("03:{$minutes}");
        });
    }
}
