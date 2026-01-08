<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Providers;

use Agenciafmd\Admix\Resources\Auth\Pages\EditProfile;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Schemas\Components\Section;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Tables\Table;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

final class FilamentPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        $this->bootDefaultTableConfigs();
        $this->bootDefaultSectionConfigs();
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admix')
            ->path('admix')
            ->login()
            ->passwordReset()
//            ->emailVerification() // TODO
//            ->emailChangeVerification() // TODO
            ->profile()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->maxContentWidth(Width::Full)
            ->font('Inter')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverPages(in: __DIR__ . '/../Pages', for: 'Agenciafmd\Admix\Pages')
            ->discoverResources(in: __DIR__ . '/../Resources', for: 'Agenciafmd\Admix\Resources')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->authGuard('admix-web')
            ->authPasswordBroker('admix-users')
            ->profile(EditProfile::class, isSimple: false)
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    private function bootDefaultTableConfigs(): void
    {
        Table::configureUsing(static function (Table $table): void {
            $table
                ->paginated([10, 25, 50, 100])
                ->defaultPaginationPageOption(100);
        });
    }

    private function bootDefaultSectionConfigs(): void
    {
        Section::configureUsing(static function (Section $section): void {
            $section->compact();
        });
    }
}
