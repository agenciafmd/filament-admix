<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Providers;

use Agenciafmd\Admix\Resources\Auth\Pages\EditProfile;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Operation;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Vite;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\View\Middleware\ShareErrorsFromSession;

final class FilamentPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        $this->bootDefaultTableConfigs();
        $this->bootDefaultSectionConfigs();
        $this->bootDefaultFormComponents();
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
            ->font(config('filament-admix.font', 'Inter'))
            ->colors(config('filament-admix.colors', [
                'primary' => Color::Blue,
            ]))
            ->brandLogo(fn (): HtmlString => new HtmlString(file_get_contents(resource_path('filament/filament-admix/svg/logo.svg'))))
            ->brandLogoHeight('2rem')
            ->favicon(fn (): HtmlString => new HtmlString(file_get_contents(resource_path('filament/filament-admix/svg/favicon.svg'))))
            ->discoverPages(
                in: __DIR__ . '/../Pages',
                for: 'Agenciafmd\Admix\Pages',
            )
            ->discoverResources(
                in: __DIR__ . '/../Resources',
                for: 'Agenciafmd\Admix\Resources',
            )
            ->plugins(collect(config('filament-admix.plugins', []))
                ->map(fn ($plugin) => new $plugin())
                ->toArray())
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/Widgets'),
                for: 'App\Filament\Widgets',
            )
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->authGuard('admix-web')
            ->authPasswordBroker('admix-users')
            ->databaseNotifications()
            ->profile(EditProfile::class, isSimple: false)
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->viteTheme('resources/filament/filament-admix/css/theme.css', 'filament-admix');
    }

    private function bootDefaultTableConfigs(): void
    {
        Table::configureUsing(static function (Table $table): void {
            $table
                ->paginated([10, 25, 50, 100])
                ->defaultPaginationPageOption(100);
        });

        TextColumn::macro('limitWithTooltip', function (int $limit) {
            /** @var TextColumn $this */
            return $this->limit($limit)
                ->tooltip(function (TextColumn $column): ?string {
                    $state = $column->getState();

                    if (mb_strlen((string) $state) <= $column->getCharacterLimit()) {
                        return null;
                    }

                    return $state;
                });
        });
    }

    private function bootDefaultSectionConfigs(): void
    {
        Section::configureUsing(static function (Section $section): void {
            $section->compact();
        });
    }

    private function bootDefaultFormComponents(): void
    {
        TagsInput::configureUsing(static function (TagsInput $component): void {
            $component->trim();
        });

        TextInput::configureUsing(static function (TextInput $textInput): void {
            $textInput->dehydrateStateUsing(function (?string $state): ?string {
                return $state ? Str::trim($state) : $state;
            });
        });

        Textarea::configureUsing(static function (Textarea $textarea): void {
            $textarea->dehydrateStateUsing(function (?string $state): ?string {
                return $state ? Str::trim($state) : $state;
            });
        });

        TextInput::macro('generateSlug', function (string $slugField = 'slug') {
            /** @var TextInput $this */
            return $this
                ->live(onBlur: true)
                ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state, string $operation) use ($slugField): void {
                    if ($operation === Operation::Edit->value) {
                        return;
                    }

                    if (($get($slugField) ?? '') !== str($old)
                        ->slug()
                        ->toString()) {
                        return;
                    }

                    $set($slugField, str($state)
                        ->slug()
                        ->toString());
                });
        });
    }
}
