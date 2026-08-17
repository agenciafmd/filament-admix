## Admix

Este pacote é um starter kit para ajudar desenvolvedores.
A ideia principal é facilitar os CRUDS dos recursos mais comuns em aplicações e sites.

### Features

- Usuários: cria usuários para acesso ao painel administrativo (admix).
- Auditoria: registra ações realizadas no sistema, permitindo a restauração dos dados.

### Estrutura para criação de novos recursos / pacotes

Os recursos / pacotes devem seguir as seguintes instruções:
- o nome do pacote deve estar no plural, em inglês e prefixado por `local-`. Ex.: `local-articles`
- os arquivos do pacote deve estar dentro do diretório `packages/agenciafmd/`. Ex: `packages/agenciafmd/local-articles`
- o pacote será carregado pelo composer.json, usando um repositorio customizado do tipo `path` e com a opção `symlink` habilitada.
Ex.
```json
"repositories": {
    "agenciafmd/local-articles": {
        "type": "path",
        "url": "packages/agenciafmd/local-articles",
        "options": {
            "symlink": true
        }
    }
},
```

### Convenções de código

- todo arquivo PHP começa com `declare(strict_types=1);`
- classes de Model, Resource, Schema (Form), Table, Service, ServiceProvider e Pages (Create/Edit/List) são `final class`
- quando o Model utilizar mais de um trait, declare um `use` por linha (não combine em uma única linha)
- métodos que sobrescrevem método de classe pai (ex.: `casts()`) recebem o atributo `#[Override]` (`use Override;`)

### Estrutura de arquivos

/config/local-articles.php
/database/factories/ArticleFactory.php
/database/migrations/YYYY_MM_DD_HHMMSS_create_articles_table.php
/database/seeders/ArticleSeeder.php
/lang/pt_BR/fields.php
/lang/pt_BR.json
/src/Models/Article.php
/src/Providers/ArticleServiceProvider.php
/src/Providers/CommandServiceProvider.php
/src/Resources/Articles/Pages/CreateArticle.php
/src/Resources/Articles/Pages/EditArticle.php
/src/Resources/Articles/Pages/ListArticles.php
/src/Resources/Articles/Schemas/ArticleForm.php
/src/Resources/Articles/Tables/ArticlesTable.php
/src/Resources/Articles/ArticleResource.php
/src/Services/ArticleService.php
/src/ArticlesPlugin.php

- /config/local-articles.php
configuração do pacote

@verbatim
    <code-snippet name="Example content of config/local-articles.php" lang="php">
        return [
            'name' => 'Articles',
            'navigation_group' => null,
            'navigation_sort' => 6,
        ];
    </code-snippet>
@endverbatim

- /database/factories/ArticleFactory.php
fabrica de dados para inserirmos no banco

@verbatim
    <code-snippet name="Example content of ArticleFactory" lang="php">
        public function definition(): array
        {
            $title = fake()->sentence(4);
            $slug = str($title)->slug();

            return [
                'is_active' => fake()->boolean(),
                'star' => fake()->boolean(),
                'title' => $title,
                'subtitle' => fake()->sentence(8),
                'summary' => fake()->text(),
                'content' => fake()->htmlParagraphs(),
                'video' => fake()->youtubeRandomUri(),
                'published_at' => fake()->dateTimeBetween(now()->subMonths(6), now()->addDay()),
                'tags' => fake()->tags(),
                'image' => Storage::putFile('fake', fake()->localImage(ratio: '16:9')),
                'images' => collect(range(0, fake()->numberBetween(1, 6)))
                    ->map(fn () => Storage::putFile('fake', fake()->localImage(ratio: '16:9')))
                    ->toArray(),
                'slug' => $slug,
            ];
        }
    </code-snippet>
@endverbatim

utilize a relação de valores abaixo para os campos, caso sejam solicitados.

| campo | padrão |
|------------+--------------|
| is_active | fake()->boolean() |
| star | fake()->boolean() |
| name | fake()->sentence(4) |
| title | fake()->sentence(4) |
| subtitle | fake()->sentence(8) |
| author | fake()->firstName . ' ' . fake()->lastName |
| summary | fake()->text() |
| published_at | fake()->dateTimeBetween(now()->subMonths(6), now()->addDay()) |
| content | fake()->htmlParagraphs() |
| description | fake()->htmlParagraphs() |
| tags | fake()->tags() |
| video | fake()->youtubeRandomUri() |
| image | Storage::putFile('fake', fake()->localImage(ratio: '16:9')) |
| images | collect(range(0, fake()->numberBetween(1, 6)))->map(fn () => Storage::putFile('fake', fake()->localImage(ratio: '16:9'))) ->toArray() |
| slug | str($title)->slug() |

- /database/migrations/YYYY_MM_DD_HHMMSS_create_articles_table.php
não utilize o metodo `down` e remova os `dock blocks`, caso existam
separe as migrações em 1 arquivo por recurso ou tabela
adicione `->index()` para os campos booleanos
adicione `->nullable()` para os campos que não são obrigatórios
adicione os campos `created_at`, `updated_at` e `deleted_at` utilizando os metodos `$table->timestamps()` e `$table->softDeletes()`

@verbatim
    <code-snippet name="Example content of create_articles_table migration" lang="php">
        public function up(): void
        {
            Schema::create('articles', static function (Blueprint $table) {
                $table->id();
                $table->boolean('is_active')
                    ->default(true)
                    ->unsigned()
                    ->index();
                $table->boolean('star')
                    ->default(false)
                    ->unsigned()
                    ->index();
                $table->string('title');
                $table->string('subtitle')
                    ->nullable();
                $table->string('author')
                    ->nullable();
                $table->text('summary')
                    ->nullable();
                $table->longText('content')
                    ->nullable();
                $table->string('video')
                    ->nullable();
                $table->timestamp('published_at')
                    ->nullable();
                $table->text('tags')
                    ->nullable();
                $table->text('image')
                    ->nullable();
                $table->text('images')
                    ->nullable();
                $table->string('slug')
                    ->unique()
                    ->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    </code-snippet>
@endverbatim

- /database/seeders/ArticleSeeder.php

@verbatim
    <code-snippet name="Example content of ArticleSeeder" lang="php">
        public function run(): void
        {
            Article::query()
                ->truncate();

            Article::factory()
                ->count(50)
                ->create();
        }
    </code-snippet>
@endverbatim

- /lang/pt_BR/fields.php

@verbatim
    <code-snippet name="Example content of fields" lang="php">
        return [
            //
        ];
    </code-snippet>
@endverbatim

- /lang/pt_BR.json
utilizado para aplicar traduções nos labels dos campos

@verbatim
    <code-snippet name="Example content of pt_BR.json" lang="json">
        {
            "Articles": "Artigos",
            "Article": "Artigo",
            "Title": "Título",
            "Subtitle": "Subtítulo",
            "Summary": "Resumo",
            "Content": "Conteúdo",
            "Image": "Imagem",
            "Images": "Imagens",
            "Star": "Destaque",
            "Published at": "Data de publicação",
            "Published from": "Publicado a partir de",
            "Published until": "Publicado até",
            "Author": "Autor",
            "Tags": "Marcadores"
        }
    </code-snippet>
@endverbatim

- /src/Models/Article.php
não utilizar o fillable
utilize a trait `WithScopes` (`Agenciafmd\Admix\Traits\WithScopes`) para os scopes `isActive` e `sort` — ela lê a propriedade `$defaultSort` do Model, então não reimplemente ordenação manualmente

@verbatim
    <code-snippet name="Example of content of Article" lang="php">
        declare(strict_types=1);

        namespace Agenciafmd\Articles\Models;

        use Agenciafmd\Admix\Traits\WithScopes;
        use Agenciafmd\Articles\Database\Factories\ArticleFactory;
        use Illuminate\Database\Eloquent\Attributes\UseFactory;
        use Illuminate\Database\Eloquent\Builder;
        use Illuminate\Database\Eloquent\Factories\HasFactory;
        use Illuminate\Database\Eloquent\Model;
        use Illuminate\Database\Eloquent\Prunable;
        use Illuminate\Database\Eloquent\SoftDeletes;
        use Override;
        use OwenIt\Auditing\Auditable;
        use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

        #[UseFactory(ArticleFactory::class)]
        final class Article extends Model implements AuditableContract
        {
            use Auditable;
            use HasFactory;
            use Prunable;
            use SoftDeletes;
            use WithScopes;

            protected array $defaultSort = [
                'is_active' => 'desc',
                'star' => 'desc',
                'published_at' => 'desc',
                'title' => 'asc',
            ];

            public function prunable(): Builder
            {
                return self::query()
                    ->where('deleted_at', '<=', now()->subDays(30));
            }

            #[Override]
            protected function casts(): array
            {
                return [
                    'is_active' => 'boolean',
                    'star' => 'boolean',
                    'tags' => 'array',
                    'images' => 'array',
                    'published_at' => 'timestamp',
                ];
            }
        }
    </code-snippet>
@endverbatim

utilize a relação de valores abaixo para os campos no casts, caso sejam solicitados.
| campo | padrão |
|------------+--------------|
| is_active | boolean() |
| star | boolean() |
| tags | array |
| images | array |
| published_at | timestamps |

a ordem de `$defaultSort`, quando disponíveis, segue os campos: is_active, star, published_at e title ou name

- /src/Providers/ArticleServiceProvider.php
responsável por registrar os recursos do pacote

@verbatim
    <code-snippet name="Example content of ArticleServiceProvider" lang="php">
        declare(strict_types=1);

        namespace Agenciafmd\Articles\Providers;

        use Illuminate\Support\ServiceProvider;

        final class ArticleServiceProvider extends ServiceProvider
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
                $this->app->register(CommandServiceProvider::class);
            }

            private function bootMigrations(): void
            {
                $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
            }

            private function bootTranslations(): void
            {
                $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'local-articles');
                $this->loadJsonTranslationsFrom(__DIR__ . '/../../lang');
            }

            private function registerConfigs(): void
            {
                $this->mergeConfigFrom(__DIR__ . '/../../config/local-articles.php', 'local-articles');
            }
        }
    </code-snippet>
@endverbatim

- /src/Providers/CommandServiceProvider.php
responsável por registrar os comandos e agendamentos do pacote

@verbatim
    <code-snippet name="Example content of CommandServiceProvider" lang="php">
        declare(strict_types=1);

        namespace Agenciafmd\Articles\Providers;

        use Agenciafmd\Articles\Models\Article;
        use Illuminate\Console\Scheduling\Schedule;
        use Illuminate\Support\ServiceProvider;

        final class CommandServiceProvider extends ServiceProvider
        {
            public function boot(): void
            {
                if (! $this->app->runningInConsole()) {
                    return;
                }

                $this->commands([
                    //
                ]);

                $this->app->booted(function () {
                    $schedule = $this->app->make(Schedule::class);
                    $minutes = config('filament-admix.schedule.minutes');

                    $schedule->command('model:prune', [
                        '--model' => [
                            Article::class,
                        ],
                    ])->dailyAt("03:{$minutes}");
                });
            }
        }
    </code-snippet>
@endverbatim

- /src/Resources/Articles/Pages/CreateArticle.php
registramos o resource de articles e aplicamos o trait RedirectBack para retornar para a lista após criar um novo registro

@verbatim
    <code-snippet name="Example content of CreateArticle" lang="php">
        declare(strict_types=1);

        namespace Agenciafmd\Articles\Resources\Articles\Pages;

        use Agenciafmd\Admix\Resources\Concerns\RedirectBack;
        use Agenciafmd\Articles\Resources\Articles\ArticleResource;
        use Filament\Resources\Pages\CreateRecord;

        final class CreateArticle extends CreateRecord
        {
            use RedirectBack;

            protected static string $resource = ArticleResource::class;
        }
    </code-snippet>
@endverbatim

- /src/Resources/Articles/Pages/EditArticle.php
registramos o resource de articles e aplicamos o trait RedirectBack para retornar para a lista após criar um novo registro
registramos o listener de `auditRestored` para atualizamos o registro após restaurar do audit
adicionamos no `getHeaderActions` as ações de deletar `DeleteAction::make()`, forçar deleção (ForceDeleteAction::make()) e restaurar (RestoreAction::make())

@verbatim
    <code-snippet name="Example content of EditArticle" lang="php">
        declare(strict_types=1);

        namespace Agenciafmd\Articles\Resources\Articles\Pages;

        use Agenciafmd\Admix\Resources\Concerns\RedirectBack;
        use Agenciafmd\Articles\Resources\Articles\ArticleResource;
        use Filament\Actions\DeleteAction;
        use Filament\Actions\ForceDeleteAction;
        use Filament\Actions\RestoreAction;
        use Filament\Resources\Pages\EditRecord;

        final class EditArticle extends EditRecord
        {
            use RedirectBack;

            protected static string $resource = ArticleResource::class;

            protected $listeners = [
                'auditRestored',
            ];

            public function getRelationManagers(): array
            {
                if ($this->record->trashed()) {
                    return [];
                }

                return parent::getRelationManagers();
            }

            public function auditRestored(): void
            {
                $this->fillForm();
            }

            protected function getHeaderActions(): array
            {
                return [
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                    RestoreAction::make(),
                ];
            }
        }
    </code-snippet>
@endverbatim

- /src/Resources/Articles/Pages/ListArticles.php
registramos o resource de articles
adicionamos no `getHeaderActions` as ações de criar novo registro `CreateAction::make()`

@verbatim
    <code-snippet name="Example content of ListArticles" lang="php">
        declare(strict_types=1);

        namespace Agenciafmd\Articles\Resources\Articles\Pages;

        use Agenciafmd\Articles\Resources\Articles\ArticleResource;
        use Filament\Actions\CreateAction;
        use Filament\Resources\Pages\ListRecords;

        final class ListArticles extends ListRecords
        {
            protected static string $resource = ArticleResource::class;

            protected function getHeaderActions(): array
            {
                return [
                    CreateAction::make(),
                ];
            }
        }
    </code-snippet>
@endverbatim

- /src/Resources/Articles/Schemas/ArticleForm.php
formulário do resource de articles
o layout externo é um `Grid::make(3)` com dois `Group`: o primeiro (`columnSpan(2)`) contém a seção "Geral" (__('General')) com os campos principais do recurso, o segundo contém a seção "Informações" (__('Information')) com os campos `is_active`, `star`, `published_at`, `created_at` e `updated_at`, caso sejam solicitados

@verbatim
    <code-snippet name="Example content of ArticleForm" lang="php">
        declare(strict_types=1);

        namespace Agenciafmd\Articles\Resources\Articles\Schemas;

        use Agenciafmd\Admix\Resources\Forms\Components\ImageUploadMultipleWithDefault;
        use Agenciafmd\Admix\Resources\Forms\Components\ImageUploadWithDefault;
        use Agenciafmd\Admix\Resources\Forms\Components\RichEditorWithDefault;
        use Agenciafmd\Admix\Resources\Forms\Components\YouTubeInput;
        use Agenciafmd\Admix\Resources\Infolists\Components\DateTimeEntry;
        use Agenciafmd\Articles\Services\ArticleService;
        use Filament\Forms\Components\DateTimePicker;
        use Filament\Forms\Components\TagsInput;
        use Filament\Forms\Components\Textarea;
        use Filament\Forms\Components\TextInput;
        use Filament\Forms\Components\Toggle;
        use Filament\Schemas\Components\Grid;
        use Filament\Schemas\Components\Group;
        use Filament\Schemas\Components\Section;
        use Filament\Schemas\Schema;

        final class ArticleForm
        {
            public static function configure(Schema $schema): Schema
            {
                return $schema
                    ->components([
                        Grid::make(3)
                            ->schema([
                                Group::make([
                                    Section::make(__('General'))
                                        ->schema([
                                            TextInput::make('title')
                                                ->translateLabel()
                                                ->generateSlug()
                                                ->autofocus()
                                                ->minLength(3)
                                                ->maxLength(255)
                                                ->required(),
                                            TextInput::make('slug')
                                                ->translateLabel()
                                                ->unique()
                                                ->required(),
                                            Textarea::make('summary')
                                                ->translateLabel()
                                                ->required()
                                                ->rows(5)
                                                ->columnSpanFull(),
                                            RichEditorWithDefault::make(name: 'content', directory: 'article/content')
                                                ->translateLabel()
                                                ->required()
                                                ->columnSpanFull(),
                                            YouTubeInput::make(),
                                            ImageUploadWithDefault::make(name: 'image', directory: 'article/image', fileNameField: 'title'),
                                            ImageUploadMultipleWithDefault::make(name: 'images', directory: 'article/images', fileNameField: 'title'),
                                            TagsInput::make('tags')
                                                ->translateLabel()
                                                ->suggestions(fn (): array => ArticleService::make()
                                                    ->tags()
                                                    ->toArray())
                                                ->columnSpanFull(),
                                        ])
                                        ->collapsible()
                                        ->columns()
                                        ->columnSpan(2),
                                ])
                                    ->columnSpan(2),
                                Group::make([
                                    Section::make(__('Information'))
                                        ->schema([
                                            Toggle::make('is_active')
                                                ->translateLabel()
                                                ->default(true),
                                            Toggle::make('star')
                                                ->translateLabel()
                                                ->default(false),
                                            DateTimePicker::make('published_at')
                                                ->translateLabel()
                                                ->columnSpanFull(),
                                            DateTimeEntry::make('created_at'),
                                            DateTimeEntry::make('updated_at'),
                                        ])
                                        ->collapsible()
                                        ->columns(),
                                ]),
                            ])
                            ->columnSpanFull(),
                    ]);
            }
        }
    </code-snippet>
@endverbatim

utilize a relação de valores abaixo para os campos do formulário, caso sejam solicitados.
- title ou name
- utilize o macro `->generateSlug()` (registrado em `TextInput`) para sincronizar automaticamente o campo `slug` — não reimplemente o closure manual de `afterStateUpdated`

@verbatim
    <code-snippet name="Example content of title ou name field" lang="php">
        TextInput::make('title')
            ->translateLabel()
            ->generateSlug()
            ->autofocus()
            ->minLength(3)
            ->maxLength(255)
            ->required(),
    </code-snippet>
@endverbatim

- slug

@verbatim
    <code-snippet name="Example content of slug field" lang="php">
        TextInput::make('slug')
            ->translateLabel()
            ->unique()
            ->required(),
    </code-snippet>
@endverbatim

- sumary ou description

@verbatim
    <code-snippet name="Example content of summary or description field" lang="php">
        Textarea::make('summary')
            ->translateLabel()
            ->required()
            ->rows(5)
            ->columnSpanFull(),
    </code-snippet>
@endverbatim

- video

@verbatim
    <code-snippet name="Example content of video field" lang="php">
        YouTubeInput::make(),
    </code-snippet>
@endverbatim

- tags

@verbatim
    <code-snippet name="Example content of tags field" lang="php">
        TagsInput::make('tags')
            ->translateLabel()
            ->suggestions(fn (): array => ArticleService::make()
            ->tags()
            ->toArray())
            ->columnSpanFull(),
    </code-snippet>
@endverbatim

- image
no valor do campo `directory`, utilize o formato `{recurso}/{campo}`, ex: `article/image`
no valor do campo `fileNameField`, utilize o campo `title` ou `name`, conforme o caso

@verbatim
    <code-snippet name="Example content of image field" lang="php">
        ImageUploadWithDefault::make(name: 'image', directory: 'article/image', fileNameField: 'title'),
    </code-snippet>
@endverbatim

- images
no valor do campo `directory`, utilize o formato `{recurso}/{campo}`, ex: `article/images`
no valor do campo `fileNameField`, utilize o campo `title` ou `name`, conforme o caso

@verbatim
    <code-snippet name="Example content of images field" lang="php">
        ImageUploadMultipleWithDefault::make(name: 'images', directory: 'article/images', fileNameField: 'title'),
    </code-snippet>
@endverbatim

- is_active

@verbatim
    <code-snippet name="Example content of is_active field" lang="php">
        Toggle::make('is_active')
            ->translateLabel()
            ->default(true),
    </code-snippet>
@endverbatim

- star

@verbatim
    <code-snippet name="Example content of star field" lang="php">
        Toggle::make('is_active')
            ->translateLabel()
            ->default(false),
    </code-snippet>
@endverbatim

- published_at

@verbatim
    <code-snippet name="Example content of published_at field" lang="php">
        DateTimePicker::make('published_at')
            ->translateLabel()
            ->columnSpanFull(),
    </code-snippet>
@endverbatim

- relacionamentos do tipo belongsToMany

@verbatim
    <code-snippet name="Example content of belongsToMany relationship field" lang="php">
        CheckboxList::make('relationship_name')
            ->translateLabel()
            ->relationship('relationship_name', 'display_field')
            ->searchable()
            ->bulkToggleable()
            ->columns(3)
            ->gridDirection(GridDirection::Row)
            ->columnSpanFull(),
    </code-snippet>
@endverbatim

- /src/Resources/Articles/Tables/ArticlesTable.php
tabela do resource de articles
a listagem principal dos campos, quando disponíveis, são: title ou name, published_at, star e is_active
os filtros principais, quando disponíveis, são: is_active, star, tags e published_at
na ação padrão de ordenação (defaultSort), utilize o scope `sort` da trait `WithScopes` (`$query->sort()`) — ele já ordena pelos campos definidos em `$defaultSort` no Model
o `BulkActionGroup`, deve conter `DeleteBulkAction::make()`, `ForceDeleteBulkAction::make()` e `RestoreBulkAction::make()`

@verbatim
    <code-snippet name="Example content of ArticlesTable" lang="php">
        declare(strict_types=1);

        namespace Agenciafmd\Articles\Resources\Articles\Tables;

        use Agenciafmd\Articles\Services\ArticleService;
        use Filament\Actions\BulkActionGroup;
        use Filament\Actions\DeleteBulkAction;
        use Filament\Actions\EditAction;
        use Filament\Actions\ForceDeleteBulkAction;
        use Filament\Actions\RestoreBulkAction;
        use Filament\Forms\Components\DateTimePicker;
        use Filament\Tables\Columns\TextColumn;
        use Filament\Tables\Columns\ToggleColumn;
        use Filament\Tables\Filters\Filter;
        use Filament\Tables\Filters\SelectFilter;
        use Filament\Tables\Filters\TernaryFilter;
        use Filament\Tables\Filters\TrashedFilter;
        use Filament\Tables\Table;
        use Illuminate\Database\Eloquent\Builder;

        final class ArticlesTable
        {
            public static function configure(Table $table): Table
            {
                return $table
                    ->columns([
                        TextColumn::make('title')
                            ->translateLabel()
                            ->sortable()
                            ->searchable(),
                        TextColumn::make('published_at')
                            ->translateLabel()
                            ->dateTime(config('filament-admix.timestamp.format'))
                            ->sortable(),
                        ToggleColumn::make('star')
                            ->translateLabel()
                            ->sortable(),
                        ToggleColumn::make('is_active')
                            ->translateLabel()
                            ->sortable(),
                    ])
                    ->filters([
                        TernaryFilter::make('is_active')
                            ->translateLabel(),
                        TernaryFilter::make('star')
                            ->translateLabel(),
                        SelectFilter::make('tags')
                            ->translateLabel()
                            ->options(fn (): array => ArticleService::make()
                                ->tags()
                                ->toArray())
                            ->query(function (Builder $query, array $data): Builder {
                                return $query->when($data['value'], fn (Builder $query, $value): Builder => $query->whereJsonContains('tags', $value));
                            }),
                        Filter::make('published_at')
                            ->schema([
                                DateTimePicker::make('published_from')
                                    ->translateLabel(),
                                DateTimePicker::make('published_until')
                                    ->translateLabel(),
                            ])
                            ->query(function (Builder $query, array $data): Builder {
                                return $query
                                    ->when(
                                        $data['published_from'],
                                        fn (Builder $query, $date): Builder => $query->whereDate('published_at', '>=', $date),
                                    )
                                    ->when(
                                        $data['published_until'],
                                        fn (Builder $query, $date): Builder => $query->whereDate('published_at', '<=', $date),
                                    );
                            }),
                        TrashedFilter::make(),
                    ])
                    ->recordActions([
                        EditAction::make(),
                    ])
                    ->toolbarActions([
                        BulkActionGroup::make([
                            DeleteBulkAction::make(),
                            ForceDeleteBulkAction::make(),
                            RestoreBulkAction::make(),
                        ]),
                    ])
                    ->defaultSort(fn (Builder $query): Builder => $query->sort());
            }
        }
    </code-snippet>
@endverbatim

- /src/Resources/Articles/ArticleResource.php
resource de articles
`getNavigationSort()` e `getNavigationGroup()` leem do config do pacote, permitindo reordenar/reagrupar o menu sem alterar código

@verbatim
    <code-snippet name="Example content of ArticleResource" lang="php">
        declare(strict_types=1);

        namespace Agenciafmd\Articles\Resources\Articles;

        use Agenciafmd\Articles\Models\Article;
        use Agenciafmd\Articles\Resources\Articles\Pages\CreateArticle;
        use Agenciafmd\Articles\Resources\Articles\Pages\EditArticle;
        use Agenciafmd\Articles\Resources\Articles\Pages\ListArticles;
        use Agenciafmd\Articles\Resources\Articles\Schemas\ArticleForm;
        use Agenciafmd\Articles\Resources\Articles\Tables\ArticlesTable;
        use BackedEnum;
        use Filament\Resources\Resource;
        use Filament\Schemas\Schema;
        use Filament\Support\Icons\Heroicon;
        use Filament\Tables\Table;
        use Illuminate\Database\Eloquent\Builder;
        use Illuminate\Database\Eloquent\SoftDeletingScope;
        use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

        final class ArticleResource extends Resource
        {
            protected static ?string $model = Article::class;

            protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

            protected static ?string $recordTitleAttribute = 'title';

            public static function getModelLabel(): string
            {
                return __('Article');
            }

            public static function getPluralModelLabel(): string
            {
                return __('Articles');
            }

            public static function getNavigationSort(): ?int
            {
                return config('local-articles.navigation_sort');
            }

            public static function getNavigationGroup(): ?string
            {
                return config('local-articles.navigation_group');
            }

            public static function form(Schema $schema): Schema
            {
                return ArticleForm::configure($schema);
            }

            public static function table(Table $table): Table
            {
                return ArticlesTable::configure($table);
            }

            public static function getRelations(): array
            {
                return [
                    AuditsRelationManager::class,
                ];
            }

            public static function getPages(): array
            {
                return [
                    'index' => ListArticles::route('/'),
                    'create' => CreateArticle::route('/create'),
                    'edit' => EditArticle::route('/{record}/edit'),
                ];
            }

            public static function getRecordRouteBindingEloquentQuery(): Builder
            {
                return parent::getRecordRouteBindingEloquentQuery()
                    ->withoutGlobalScopes([
                        SoftDeletingScope::class,
                    ]);
            }
        }
    </code-snippet>
@endverbatim

- /src/Services/ArticleService.php
serviço do resource de articles
usado quando precisamos de regras de negócio específicas
no caso abaixo, para obter a lista de tags únicas já cadastradas e utilizarmos no formulário e tabela

@verbatim
    <code-snippet name="Example content of ArticleService" lang="php">
        declare(strict_types=1);

        namespace Agenciafmd\Articles\Services;

        use Agenciafmd\Articles\Models\Article;
        use Illuminate\Database\Eloquent\Builder;
        use Illuminate\Support\Collection;

        final class ArticleService
        {
            public static function make(): static
            {
                return app(self::class);
            }

            public function tags(): Collection
            {
                return $this->queryBuilder()
                    ->pluck('tags')
                    ->filter()
                    ->flatten()
                    ->unique()
                    ->mapWithKeys(fn ($item) => [$item => $item])
                    ->sort();
            }

            private function queryBuilder(): Builder
            {
                return Article::query();
            }
        }
    </code-snippet>
@endverbatim

- /src/ArticlesPlugin.php
classe principal do pacote
aqui registramos o resource no painel administrativo (admix)

@verbatim
    <code-snippet name="Example content of ArticlesPlugin" lang="php">
        declare(strict_types=1);

        namespace Agenciafmd\Articles;

        use Agenciafmd\Articles\Resources\Articles\ArticleResource;
        use Filament\Contracts\Plugin;
        use Filament\Panel;

        final class ArticlesPlugin implements Plugin
        {
            public static function make(): static
            {
                return app(self::class);
            }

            public function getId(): string
            {
                return 'articles';
            }

            public function register(Panel $panel): void
            {
                $panel
                    ->resources([
                        ArticleResource::class,
                    ]);
            }

            public function boot(Panel $panel): void
            {
                //
            }
        }
    </code-snippet>
@endverbatim

### Componentes reutilizáveis do Admix

Antes de criar um novo componente de formulário, verifique se já existe um equivalente no pacote `filament-admix`. Evite reimplementar upload de arquivo/vídeo, seletor de ícone, campo de senha, etc.

| componente | namespace | descrição |
|------------+-----------+-----------|
| ImageUploadWithDefault | Agenciafmd\Admix\Resources\Forms\Components | upload de imagem única, com editor de imagem |
| ImageUploadMultipleWithDefault | Agenciafmd\Admix\Resources\Forms\Components | upload de múltiplas imagens, com editor de imagem |
| FileUploadWithDefault | Agenciafmd\Admix\Resources\Forms\Components | upload de arquivo genérico, com nome de arquivo derivado de outro campo |
| VideoUploadWithDefault | Agenciafmd\Admix\Resources\Forms\Components | upload de vídeo (mp4), baseado em FileUploadWithDefault |
| RichEditorWithDefault | Agenciafmd\Admix\Resources\Forms\Components | editor de texto rico (rich editor) com configuração padrão do pacote |
| YouTubeInput | Agenciafmd\Admix\Resources\Forms\Components | campo de URL de vídeo do YouTube |
| IconPickerWithDefault | Agenciafmd\Admix\Resources\Forms\Components | seletor de ícone (heroicons/tabler/frontend) |
| PasswordInput | Agenciafmd\Admix\Resources\Forms\Components | campo de senha com regra de validação e `dehydrated` condicional |
| DateTimePickerDisabled | Agenciafmd\Admix\Resources\Forms\Components | campo de data/hora desabilitado, oculto na criação (ex.: `created_at`/`updated_at` editáveis só na edição) |
| DateTimeEntry | Agenciafmd\Admix\Resources\Infolists\Components | exibição (infolist) de data/hora, usado em `created_at`/`updated_at` no formulário |

Traits e concerns reutilizáveis:

| trait/concern | namespace | descrição |
|------------+-----------+-----------|
| RedirectBack | Agenciafmd\Admix\Resources\Concerns | usado nas Pages de Create/Edit para retornar à listagem após salvar |
| WithScopes | Agenciafmd\Admix\Traits | fornece os scopes `isActive` e `sort` para o Model; leia `$defaultSort` em vez de reimplementar ordenação |
