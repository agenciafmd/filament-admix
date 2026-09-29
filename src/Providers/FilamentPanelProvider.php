<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Providers;

use Agenciafmd\Admix\Http\Middleware\AutoLogin;
use Agenciafmd\Admix\Models\User;
use Agenciafmd\Admix\Permissions\PermissionRegistry;
use Agenciafmd\Admix\Policies\ResourcePolicy;
use Agenciafmd\Admix\Resources\Auth\Pages\EditProfile;
use Filament\Contracts\Plugin;
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
use Filament\Support\Facades\FilamentView;
use Filament\Tables\Columns\CheckboxColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Override;

final class FilamentPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        $this->bootDefaultTableConfigs();
        $this->bootDefaultSectionConfigs();
        $this->bootDefaultFormComponents();
        $this->bootStagingAlert();
        $this->bootPermissions();
    }

    #[Override]
    public function register(): void
    {
        parent::register();

        $this->app->singleton(PermissionRegistry::class);
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
            ->font(config()->string('filament-admix.font', 'Inter'))
            ->colors(fn (): mixed => config('filament-admix.colors', [
                'primary' => Color::Blue,
            ]))
            ->brandLogo(fn (): HtmlString => new HtmlString(File::get(resource_path('filament/filament-admix/svg/logo.svg'))))
            ->brandLogoHeight('2rem')
            ->favicon(fn (): HtmlString => new HtmlString(File::get(resource_path('filament/filament-admix/svg/favicon.svg'))))
            ->discoverPages(
                in: __DIR__ . '/../Pages',
                for: 'Agenciafmd\Admix\Pages',
            )
            ->discoverResources(
                in: __DIR__ . '/../Resources',
                for: 'Agenciafmd\Admix\Resources',
            )
            ->plugins(collect(config()->array('filament-admix.plugins', []))
                ->filter(static fn (mixed $plugin): bool => is_string($plugin) && class_exists($plugin))
                ->map(static fn (string $plugin): ?Plugin => ($instance = new $plugin()) instanceof Plugin ? $instance : null)
                ->filter()
                ->all())
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
                AutoLogin::class,
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

        /**
         * Inline editable columns bypass policies, so they are disabled
         * when the user cannot update the record.
         */
        foreach ([CheckboxColumn::class, SelectColumn::class, TextInputColumn::class, ToggleColumn::class] as $editableColumn) {
            $editableColumn::configureUsing(static function (CheckboxColumn|SelectColumn|TextInputColumn|ToggleColumn $column): void {
                $column->disabled(fn (Model $record): bool => Gate::denies('update', $record));
            });
        }

        TextColumn::macro('limitWithTooltip', fn (int $limit): TextColumn => $this->limit($limit)
            ->tooltip(function (TextColumn $column): ?string {
                $state = $column->getState();

                if (! is_scalar($state)) {
                    return null;
                }

                $text = (string) $state;

                if (mb_strlen($text) <= $column->getCharacterLimit()) {
                    return null;
                }

                return $text;
            }));
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
            $textInput->dehydrateStateUsing(fn (?string $state): ?string => $state ? Str::trim($state) : $state);
        });

        Textarea::configureUsing(static function (Textarea $textarea): void {
            $textarea->dehydrateStateUsing(fn (?string $state): ?string => $state ? Str::trim($state) : $state);
        });

        TextInput::macro('generateSlug', fn (string $slugField = 'slug'): TextInput => $this
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
            }));
    }

    /**
     * Registers the generic policy for every resource model without its own policy,
     * and checks the extra abilities declared by resources (`getExtraPermissions()`).
     */
    private function bootPermissions(): void
    {
        $registry = resolve(PermissionRegistry::class);

        foreach (array_keys($registry->resourcesByModel()) as $model) {
            if (Gate::getPolicyFor($model) === null) {
                Gate::policy($model, ResourcePolicy::class);
            }
        }

        Gate::before(static function (mixed $user, string $ability, array $arguments) use ($registry): ?bool {
            $model = $arguments[0] ?? null;

            if (! $user instanceof User || ! $model instanceof Model && ! is_string($model)) {
                return null;
            }

            $resource = $registry->resourceFor($model);

            if ($resource === null || ! array_key_exists($ability, $registry->extraAbilitiesFor($resource))) {
                return null;
            }

            return $user->hasPermission(PermissionRegistry::permissionKey($resource, $ability));
        });
    }

    private function bootStagingAlert(): void
    {
        if (config('app.env') === 'production') {
            return;
        }

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_START,
            fn (): string => Blade::render('filament-admix::filament.staging-banner'),
        );
    }
}
