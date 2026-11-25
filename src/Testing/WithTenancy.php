<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Testing;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

/**
 * Enables the admix tenancy in a test case. The panel routes depend on the tenancy, so the
 * config is set before the application boots: `pest()->extend(TestCase::class)->use(WithTenancy::class)`.
 */
// @phpstan-ignore trait.unused (used by the models and tests of the projects)
trait WithTenancy
{
    public function createApplication(): Application
    {
        $app = require Application::inferBasePath() . '/bootstrap/app.php';

        $app->booting(static fn () => config(['filament-admix.tenancy.enabled' => true]));
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
