<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Description('Remove notification more than x days')]
#[Signature('notifications:clear
        {days? : How many days you want to keep the notifications.}')]
final class NotificationsClear extends Command
{
    public function handle(): int
    {
        $days = $this->argument('days') ?: $this->ask('How many days do you want to keep the notifications?', '30');

        if (! is_numeric($days)) {
            $this->components->error('The number of days must be numeric.');

            return self::FAILURE;
        }

        DB::table('notifications')
            ->where('created_at', '<=', today()
                ->subDays((int) $days))
            ->delete();

        $this->info('Done!');

        return self::SUCCESS;
    }
}
