<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Providers;

use Illuminate\Support\ServiceProvider;

final class AdmixServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->bootProviders();

        $this->bootMigrations();

        $this->bootTranslations();
    }

    public function register(): void
    {
        $this->registerConfigs();
    }

    private function bootProviders(): void
    {
        $this->app->register(FilamentPanelProvider::class);
        $this->app->register(CommandServiceProvider::class);
    }

    private function bootMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    private function bootTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'admix');
        $this->loadJsonTranslationsFrom(__DIR__ . '/../../lang');
    }

    private function registerConfigs(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/admix.php', 'admix');
        //        $this->mergeConfigFrom(__DIR__.'/../../config/audit.php', 'audit');
        //        $this->mergeConfigFrom(__DIR__.'/../../config/audit-alias.php', 'audit-alias');
        //        $this->mergeConfigFrom(__DIR__ . '/../config/local-operations.php', 'local-operations');
        //        $this->mergeConfigFrom(__DIR__ . '/../config/upload-configs.php', 'upload-configs');
    }
}
