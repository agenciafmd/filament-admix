<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Commands;

use Illuminate\Console\Command;
use OwenIt\Auditing\Models\Audit;

final class AuditPrune extends Command
{
    protected $signature = 'audit:prune
        {days=180 : How many days you want to keep the audits.}';

    protected $description = 'Prune audit records that are no longer needed';

    public function handle(): void
    {
        $days = (int) $this->argument('days');

        $count = Audit::query()
            ->where('created_at', '<=', now()->subDays($days))
            ->forceDelete();

        $this->components->info("Deleted {$count} audit records.");
    }
}
