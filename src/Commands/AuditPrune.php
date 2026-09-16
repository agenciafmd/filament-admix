<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use OwenIt\Auditing\Models\Audit;

#[Description('Prune audit records that are no longer needed')]
#[Signature('audit:prune
        {days=180 : How many days you want to keep the audits.}')]
final class AuditPrune extends Command
{
    public function handle(): void
    {
        $days = (int) $this->argument('days');

        $count = Audit::query()
            ->where('created_at', '<=', now()->subDays($days))
            ->forceDelete();

        $this->components->info("Deleted {$count} audit records.");
    }
}
