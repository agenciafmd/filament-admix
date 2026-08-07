<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Providers;

use Illuminate\Support\ServiceProvider;

final class BladeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->bootViews();

        $this->bootPublish();
    }

    public function register(): void
    {
        //
    }

    private function bootViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'filament-admix');
    }

    private function bootPublish(): void
    {
        $this->publishes([
            __DIR__ . '/../../resources/views' => base_path('resources/views/vendor/agenciafmd/filament-admix/views'),
        ], 'filament-admix:views');
    }
}
