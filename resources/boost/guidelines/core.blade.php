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

### Estrutura de arquivos

/config/local-articles.php
/database/factories/ArticleFactory.php
/database/migrations/YYYY_MM_DD_HHMMSS_create_articles_table.php
/database/seeders/ArticleSeeder.php
/lang/pt_BR/fields.php
/lang/pt_BR.json
/src/Models/Article.php
/src/Providers/ArticleServiceProvider.php
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
            $slug = str()->slug($title);

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
| slug | str()->slug($title) |

- /database/migrations/YYYY_MM_DD_HHMMSS_create_articles_table.php
não utilize o metodo `down` e remova os `dock blocks`, caso existam
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
@verbatim
    <code-snippet name="Example of content of Article" lang="php">
        use Agenciafmd\Articles\Database\Factories\ArticleFactory;
        use Illuminate\Database\Eloquent\Attributes\UseFactory;
        use Illuminate\Database\Eloquent\Builder;
        use Illuminate\Database\Eloquent\Factories\HasFactory;
        use Illuminate\Database\Eloquent\Model;
        use Illuminate\Database\Eloquent\Prunable;
        use Illuminate\Database\Eloquent\SoftDeletes;
        use OwenIt\Auditing\Auditable;
        use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

        #[UseFactory(ArticleFactory::class)]
        class Article extends Model implements AuditableContract
        {
            use Auditable, HasFactory, Prunable, SoftDeletes;

            public function prunable(): Builder
            {
                return self::query()
                    ->where('deleted_at', '<=', now()->subDays(30));
            }

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

- /src/Providers/ArticleServiceProvider.php
responsável por registrar os recursos do pacote
@verbatim
    <code-snippet name="Example content of ArticleServiceProvider" lang="php">
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
                //
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

- /src/Resources/Articles/Pages/CreateArticle.php
registramos o resource de articles e aplicamos o trait RedirectBack para retornar para a lista após criar um novo registro
@verbatim
    <code-snippet name="Example content of CreateArticle" lang="php">
        namespace Agenciafmd\Articles\Resources\Articles\Pages;

        use Agenciafmd\Admix\Resources\Concerns\RedirectBack;
        use Agenciafmd\Articles\Resources\Articles\ArticleResource;
        use Filament\Resources\Pages\CreateRecord;

        class CreateArticle extends CreateRecord
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
        namespace Agenciafmd\Articles\Resources\Articles\Pages;

        use Agenciafmd\Admix\Resources\Concerns\RedirectBack;
        use Agenciafmd\Articles\Resources\Articles\ArticleResource;
        use Filament\Actions\DeleteAction;
        use Filament\Actions\ForceDeleteAction;
        use Filament\Actions\RestoreAction;
        use Filament\Resources\Pages\EditRecord;

        class EditArticle extends EditRecord
        {
            use RedirectBack;

            protected static string $resource = ArticleResource::class;

            protected $listeners = [
                'auditRestored',
            ];

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
        namespace Agenciafmd\Articles\Resources\Articles\Pages;

        use Agenciafmd\Articles\Resources\Articles\ArticleResource;
        use Filament\Actions\CreateAction;
        use Filament\Resources\Pages\ListRecords;

        class ListArticles extends ListRecords
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
separe os campos em seções (Section)
a primeira seção deve ser chamada de "Geral" (__('General')) e conter os campos principais do recurso
a segunda seção deve ser chamada de "Informações" (__('Information')) e conter os campos `is_active`, `star`, `published_at`, `created_at` e `updated_at`, caso sejam solicitados
@verbatim
    <code-snippet name="Example content of ArticleForm" lang="php">
        namespace Agenciafmd\Articles\Resources\Articles\Schemas;

        use Agenciafmd\Admix\Resources\Schemas\Components\DateTimePickerDisabled;
        use Agenciafmd\Admix\Resources\Schemas\Components\ImageUploadMultipleWithDefault;
        use Agenciafmd\Admix\Resources\Schemas\Components\ImageUploadWithDefault;
        use Agenciafmd\Admix\Resources\Schemas\Components\RichEditorWithDefault;
        use Agenciafmd\Admix\Resources\Schemas\Components\YoutubeInput;
        use Agenciafmd\Articles\Services\ArticleService;
        use Filament\Forms\Components\DateTimePicker;
        use Filament\Forms\Components\TagsInput;
        use Filament\Forms\Components\Textarea;
        use Filament\Forms\Components\TextInput;
        use Filament\Forms\Components\Toggle;
        use Filament\Schemas\Components\Section;
        use Filament\Schemas\Components\Utilities\Get;
        use Filament\Schemas\Components\Utilities\Set;
        use Filament\Schemas\Schema;

        final class ArticleForm
        {
            public static function configure(Schema $schema): Schema
            {
                return $schema
                    ->components([
                        Section::make(__('General'))
                            ->schema([
                                TextInput::make('title')
                                    ->translateLabel()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) {
                                        if (($get('slug') ?? '') !== str($old)->slug()->toString()) {
                                            return;
                                        }

                                        $set('slug', str($state)->slug()->toString());
                                        })
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
                                DateTimePickerDisabled::make('created_at'),
                                DateTimePickerDisabled::make('updated_at'),
                            ])
                            ->collapsible()
                            ->columns(),
                    ])
                    ->columns(3);
            }
        }
    </code-snippet>
@endverbatim

utilize a relação de valores abaixo para os campos do formulário, caso sejam solicitados.
- title ou name
@verbatim
    <code-snippet name="Example content of title ou name field" lang="php">
        TextInput::make('title')
            ->translateLabel()
            ->live(onBlur: true)
            ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) {
                if (($get('slug') ?? '') !== str($old)->slug()->toString()) {
                    return;
                }

                $set('slug', str($state)->slug()->toString());
            })
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
    <code-snippet name="Example content of image field" lang="php">
        ImageUploadWithDefault::make(name: 'image', directory: 'article/image', fileNameField: 'title'),
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

- /src/Resources/Articles/Tables/ArticlesTable.php
tabela do resource de articles
a listagem principal dos campos, quando disponíveis, são: title ou name, published_at, star e is_active
os filtros principais, quando disponíveis, são: is_active, star, tags e published_at
na ação padrão de ordenação (defaultSort), utilize os campos is_active, star, published_at e title ou name
o `BulkActionGroup`, deve conter `DeleteBulkAction::make()`, `ForceDeleteBulkAction::make()` e `RestoreBulkAction::make()`
@verbatim
    <code-snippet name="Example content of ArticlesTable" lang="php">
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
                    ->defaultSort(function (Builder $query): Builder {
                        return $query->orderBy('is_active', 'desc')
                            ->orderBy('star', 'desc')
                            ->orderBy('published_at', 'desc')
                            ->orderBy('title');
                    });
            }
        }
    </code-snippet>
@endverbatim

- /src/Resources/Articles/ArticleResource.php
resource de articles
@verbatim
    <code-snippet name="Example content of ArticleResource" lang="php">
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