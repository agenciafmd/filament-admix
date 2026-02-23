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

        $this->bootPublish();
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

    private function bootPublish(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/filament-admix.php' => base_path('config/filament-admix.php'),
        ], 'filament-admix:config');
        $this->publishes([
            __DIR__ . '/../../resources/public' => public_path(),
            __DIR__ . '/../../resources/css' => resource_path('css'),
            __DIR__ . '/../../resources/vite.admix.config.js' => base_path('vite.admix.config.js'),
            __DIR__ . '/../../lang/pt_BR/icon-picker.php' => base_path('lang/vendor/filament-icon-picker/pt_BR/icon-picker.php'),
        ], 'filament-admix:theme');
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
        $this->mergeConfigFrom(__DIR__ . '/../../config/filament-admix.php', 'filament-admix');
        $this->mergeConfigFrom(__DIR__ . '/../../config/audit.php', 'audit');
        $this->mergeConfigFrom(__DIR__ . '/../../config/filament-auditing.php', 'filament-auditing');
        //        $this->mergeConfigFrom(__DIR__.'/../../config/audit-alias.php', 'audit-alias');
        //        $this->mergeConfigFrom(__DIR__ . '/../config/local-operations.php', 'local-operations');
        //        $this->mergeConfigFrom(__DIR__ . '/../config/upload-configs.php', 'upload-configs');
    }
}
