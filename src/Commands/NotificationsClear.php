<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class NotificationsClear extends Command
{
    protected $signature = 'notifications:clear
        {days? : How many days you want to keep the notifications.}';

    protected $description = 'Remove notification more than x days';

    public function handle(): void
    {
        if (! $days = $this->argument('days')) {
            $days = $this->ask('How many days do you want to keep the notifications?', '30');
        }

        DB::table('notifications')
            ->where('created_at', '<=', today()
                ->subDays($days))
            ->delete();

        $this->info('Done!');
    }
}
