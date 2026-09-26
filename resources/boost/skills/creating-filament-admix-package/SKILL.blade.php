---
name: creating-filament-admix-package
description: 'Use this skill whenever creating a brand-new `local-{plural}` package under `packages/agenciafmd/` for the Filament admix starter kit — config, factory, migration, seeder, translations, Model, ServiceProviders, Create/Edit/List Pages, Resource wiring, Service and Plugin. Do not use it just to tweak the Form schema or Table of an already-existing resource — see the `filament-admix-form-fields` and `filament-admix-table-conventions` skills for that.'
license: MIT
metadata:
    author: agenciafmd
---

# Criando um novo pacote admix - /config/local-articles.php configuração do pacote

@boostsnippet('Example content of config/local-articles.php', 'php')
    return [ 'name' => 'Articles', 'navigation_group' => null, 'navigation_sort' => 6, ];
@endboostsnippet

- /database/factories/ArticleFactory.php fabrica de dados para inserirmos no banco. Declare
@verbatim`@extends Factory<Article>`@endverbatim e `protected $model = Article::class`: o Seeder usa
`ArticleFactory::new()`, que sem `$model` procuraria `App\Models\Article`. Para o slug, passe o texto para o helper
(`str($title)->slug()->toString()`); o `str()` sem argumento não é tipado

@boostsnippet('Example content of ArticleFactory', 'php')
    declare(strict_types=1); namespace Agenciafmd\Articles\Database\Factories; use Agenciafmd\Articles\Models\Article;
    use Illuminate\Database\Eloquent\Factories\Factory; use Illuminate\Support\Facades\Storage; /** @extends
    Factory<Article> */ final class ArticleFactory extends Factory { protected $model = Article::class; public
    function definition(): array { $title = fake()->sentence(4); $slug = str($title)->slug()->toString(); return [
    'is_active' => fake()->boolean(), 'star' => fake()->boolean(), 'title' => $title, 'subtitle' => fake()->sentence(8),
    'summary' => fake()->text(), 'content' => fake()->htmlParagraphs(), 'video' => fake()->youtubeRandomUri(),
    'published_at' => fake()->dateTimeBetween(now()->subMonths(6), now()->addDay()), 'tags' => fake()->tags(), 'image'
    => Storage::putFile('fake', fake()->localImage(ratio: '16:9')), 'images' => collect(range(0,
    fake()->numberBetween(1, 6))) ->map(fn () => Storage::putFile('fake', fake()->localImage(ratio: '16:9')))
    ->all(), 'slug' => $slug, ]; } }
@endboostsnippet

utilize a relação de valores abaixo para os campos, caso sejam solicitados. | campo | padrão |
|------------+--------------| | is_active | fake()->boolean() | | star | fake()->boolean() | | name |
fake()->sentence(4) | | title | fake()->sentence(4) | | subtitle | fake()->sentence(8) | | author | fake()->firstName .
' ' . fake()->lastName | | summary | fake()->text() | | published_at | fake()->dateTimeBetween(now()->subMonths(6),
now()->addDay()) | | content | fake()->htmlParagraphs() | | description | fake()->htmlParagraphs() | | tags |
fake()->tags() | | video | fake()->youtubeRandomUri() | | image | Storage::putFile('fake', fake()->localImage(ratio:
'16:9')) | | images | collect(range(0, fake()->numberBetween(1, 6)))->map(fn () => Storage::putFile('fake',
fake()->localImage(ratio: '16:9'))) ->toArray() | | slug | str($title)->slug() | -
/database/migrations/YYYY_MM_DD_HHMMSS_create_articles_table.php não utilize o metodo `down` e remova os `dock blocks`,
caso existam separe as migrações em 1 arquivo por recurso ou tabela adicione `->index()` para os campos booleanos
adicione `->nullable()` para os campos que não são obrigatórios adicione os campos `created_at`, `updated_at` e
`deleted_at` utilizando os metodos `$table->timestamps()` e `$table->softDeletes()`

@boostsnippet('Example content of create_articles_table migration', 'php')
    public function up(): void { Schema::create('articles', static function (Blueprint $table) { $table->id();
    $table->boolean('is_active') ->default(true) ->unsigned() ->index(); $table->boolean('star') ->default(false)
    ->unsigned() ->index(); $table->string('title'); $table->string('subtitle') ->nullable(); $table->string('author')
    ->nullable(); $table->text('summary') ->nullable(); $table->longText('content') ->nullable();
    $table->string('video') ->nullable(); $table->timestamp('published_at') ->nullable(); $table->text('tags')
    ->nullable(); $table->text('image') ->nullable(); $table->text('images') ->nullable(); $table->string('slug')
    ->unique() ->index(); $table->timestamps(); $table->softDeletes(); }); }
@endboostsnippet

- /database/seeders/ArticleSeeder.php

@boostsnippet('Example content of ArticleSeeder', 'php')
    public function run(): void { Schema::withoutForeignKeyConstraints(fn () => Article::query() ->truncate());
    ArticleFactory::new() ->count(50) ->create(); }
@endboostsnippet

- /lang/pt_BR/fields.php

@boostsnippet('Example content of fields', 'php')
    return [ // ];
@endboostsnippet

- /lang/pt_BR.json utilizado para aplicar traduções nos labels dos campos

@boostsnippet('Example content of pt_BR.json', 'json')
    { "Articles": "Artigos", "Article": "Artigo", "Title": "Título", "Subtitle": "Subtítulo", "Summary": "Resumo",
    "Content": "Conteúdo", "Image": "Imagem", "Images": "Imagens", "Star": "Destaque", "Published at": "Data de
    publicação", "Published from": "Publicado a partir de", "Published until": "Publicado até", "Author": "Autor",
    "Tags": "Marcadores" }
@endboostsnippet

- /src/Models/Article.php não utilizar o fillable utilize a trait `WithScopes` (`Agenciafmd\Admix\Traits\WithScopes`)
para os scopes `isActive` e `sort` — ela lê a propriedade `$defaultSort` do Model, que é obrigatória, então não
reimplemente ordenação manualmente. O `prunable()` usa `today()` (e não `now()`), para que o corte seja sempre a
meia-noite do dia. @verbatim Tipagem esperada no Model: `/** @use HasFactory<ArticleFactory> */` no trait,
`@var array<string, 'asc'|'desc'>` no `$defaultSort` e `@return Builder<self>` no `prunable()`. Accessors (`Attribute`)
declaram `@return Attribute<Tipo, never>`; um accessor que devolve `RichContentRenderer` usa `'<p></p>'` quando o
conteúdo estiver vazio, porque o renderer do Filament quebra com `null` ou string vazia @endverbatim

@boostsnippet('Example of content of Article', 'php')
    declare(strict_types=1); namespace Agenciafmd\Articles\Models; use Agenciafmd\Admix\Traits\WithScopes; use
    Agenciafmd\Articles\Database\Factories\ArticleFactory; use Illuminate\Database\Eloquent\Attributes\UseFactory; use
    Illuminate\Database\Eloquent\Builder; use Illuminate\Database\Eloquent\Factories\HasFactory; use
    Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Prunable; use
    Illuminate\Database\Eloquent\SoftDeletes; use Override; use OwenIt\Auditing\Auditable; use
    OwenIt\Auditing\Contracts\Auditable as AuditableContract; #[UseFactory(ArticleFactory::class)] final class Article
    extends Model implements AuditableContract { use Auditable; /** @use HasFactory<ArticleFactory> */ use HasFactory;
    use Prunable; use SoftDeletes; use WithScopes; /** @var array<string, 'asc'|'desc'> */ protected array $defaultSort
    = [ 'is_active' => 'desc', 'star' => 'desc', 'published_at' => 'desc', 'title' => 'asc', ]; /** @return
    Builder<self> */ public function prunable(): Builder { return self::query() ->where('deleted_at', '<=',
    today()->subDays(30)); } #[Override] protected function casts(): array { return [ 'is_active' => 'boolean', 'star'
    => 'boolean', 'tags' => 'array', 'images' => 'array', 'published_at' => 'timestamp', ]; } }
@endboostsnippet

utilize a relação de valores abaixo para os campos no casts, caso sejam solicitados. | campo | padrão |
|------------+--------------| | is_active | boolean() | | star | boolean() | | tags | array | | images | array | |
published_at | timestamps | a ordem de `$defaultSort`, quando disponíveis, segue os campos: is_active, star,
published_at e title ou name - /src/Providers/ArticleServiceProvider.php responsável por registrar os recursos do pacote

@boostsnippet('Example content of ArticleServiceProvider', 'php')
    declare(strict_types=1); namespace Agenciafmd\Articles\Providers; use Illuminate\Support\ServiceProvider; final
    class ArticleServiceProvider extends ServiceProvider { public function boot(): void { $this->bootProviders();
    $this->bootMigrations(); $this->bootTranslations(); } public function register(): void { $this->registerConfigs(); }
    private function bootProviders(): void { $this->app->register(CommandServiceProvider::class); } private function
    bootMigrations(): void { $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations'); } private function
    bootTranslations(): void { $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'local-articles');
    $this->loadJsonTranslationsFrom(__DIR__ . '/../../lang'); } private function registerConfigs(): void {
    $this->mergeConfigFrom(__DIR__ . '/../../config/local-articles.php', 'local-articles'); } }
@endboostsnippet

- /src/Providers/CommandServiceProvider.php responsável por registrar os comandos e agendamentos do pacote

@boostsnippet('Example content of CommandServiceProvider', 'php')
    declare(strict_types=1); namespace Agenciafmd\Articles\Providers; use Agenciafmd\Articles\Models\Article; use
    Illuminate\Console\Scheduling\Schedule; use Illuminate\Support\ServiceProvider; final class CommandServiceProvider
    extends ServiceProvider { public function boot(): void { if (! $this->app->runningInConsole()) { return; }
    $this->commands([ // ]); $this->app->booted(function () { $schedule = $this->app->make(Schedule::class); $minutes =
    config()->string('filament-admix.schedule.minutes', '00'); $schedule->command('model:prune', [ '--model' => [ Article::class, ],
    ])->dailyAt("03:{$minutes}"); }); } }
@endboostsnippet

- /src/Resources/Articles/Pages/CreateArticle.php registramos o resource de articles e aplicamos o trait RedirectBack
para retornar para a lista após criar um novo registro

@boostsnippet('Example content of CreateArticle', 'php')
    declare(strict_types=1); namespace Agenciafmd\Articles\Resources\Articles\Pages; use
    Agenciafmd\Admix\Resources\Concerns\RedirectBack; use Agenciafmd\Articles\Resources\Articles\ArticleResource; use
    Filament\Resources\Pages\CreateRecord; final class CreateArticle extends CreateRecord { use RedirectBack; protected
    static string $resource = ArticleResource::class; }
@endboostsnippet

- /src/Resources/Articles/Pages/EditArticle.php registramos o resource de articles e aplicamos o trait RedirectBack para
retornar para a lista após criar um novo registro registramos o listener de `auditRestored` para atualizamos o registro
após restaurar do audit adicionamos no `getHeaderActions` as ações de deletar `DeleteAction::make()`, forçar deleção
(ForceDeleteAction::make()) e restaurar (RestoreAction::make()). O `$listeners` recebe o PHPDoc com o tipo
`array<int, string>`, e o `getRelationManagers()` confere o tipo do registro com `$this->getRecord()` antes de chamar
`trashed()`

@boostsnippet('Example content of EditArticle', 'php')
    declare(strict_types=1); namespace Agenciafmd\Articles\Resources\Articles\Pages; use
    Agenciafmd\Admix\Resources\Concerns\RedirectBack; use Agenciafmd\Articles\Resources\Articles\ArticleResource; use
    Filament\Actions\DeleteAction; use Filament\Actions\ForceDeleteAction; use Filament\Actions\RestoreAction; use
    Filament\Resources\Pages\EditRecord; use Agenciafmd\Articles\Models\Article; final class EditArticle extends
    EditRecord { use RedirectBack; protected static string $resource = ArticleResource::class; /** @var array<int,
    string> */ protected $listeners = [ 'auditRestored', ]; public function getRelationManagers(): array { $record =
    $this->getRecord(); if ($record instanceof Article && $record->trashed()) { return []; } return
    parent::getRelationManagers(); }
    public function auditRestored(): void { $this->fillForm(); } protected function getHeaderActions(): array { return [
    DeleteAction::make(), ForceDeleteAction::make(), RestoreAction::make(), ]; } }
@endboostsnippet

- /src/Resources/Articles/Pages/ListArticles.php registramos o resource de articles adicionamos no `getHeaderActions` as
ações de criar novo registro `CreateAction::make()`

@boostsnippet('Example content of ListArticles', 'php')
    declare(strict_types=1); namespace Agenciafmd\Articles\Resources\Articles\Pages; use
    Agenciafmd\Articles\Resources\Articles\ArticleResource; use Filament\Actions\CreateAction; use
    Filament\Resources\Pages\ListRecords; final class ListArticles extends ListRecords { protected static string
    $resource = ArticleResource::class; protected function getHeaderActions(): array { return [ CreateAction::make(), ];
    } }
@endboostsnippet

- /src/Resources/Articles/ArticleResource.php resource de articles `getNavigationSort()` e `getNavigationGroup()` leem
do config do pacote, permitindo reordenar/reagrupar o menu sem alterar código; como o config pode estar nulo, confira o
tipo do valor (`is_int()`/`is_string()`) antes de retornar `form()`/`table()` só delegam pras classes
`ArticleForm`/`ArticlesTable` — veja as skills `filament-admix-form-fields` e `filament-admix-table-conventions` pra
montar o conteúdo delas `getRelations()` lista os RelationManagers do recurso, sempre com o
`AuditsRelationManager::class` por último; os RelationManagers próprios do pacote ficam em
/src/Resources/Articles/RelationManagers/ e as convenções deles (nome, localização entre pacotes, modo só leitura) estão
na skill `filament-admix-table-conventions` não crie Policy nem AuthServiceProvider: o admix registra a `ResourcePolicy`
automaticamente e as permissões do Resource (visualizar, criar, atualizar, deletar, restaurar, auditoria) aparecem no
formulário de Grupos; se o Resource tiver actions próprias (enviar, aprovar...), declare-as em `getExtraPermissions()` e
proteja a action com `->authorize()` — veja a skill `filament-admix-permissions`

@boostsnippet('Example content of ArticleResource', 'php')
    declare(strict_types=1); namespace Agenciafmd\Articles\Resources\Articles; use Agenciafmd\Articles\Models\Article;
    use Agenciafmd\Articles\Resources\Articles\Pages\CreateArticle; use
    Agenciafmd\Articles\Resources\Articles\Pages\EditArticle; use
    Agenciafmd\Articles\Resources\Articles\Pages\ListArticles; use
    Agenciafmd\Articles\Resources\Articles\Schemas\ArticleForm; use
    Agenciafmd\Articles\Resources\Articles\Tables\ArticlesTable; use BackedEnum; use Filament\Resources\Resource; use
    Filament\Schemas\Schema; use Filament\Support\Icons\Heroicon; use Filament\Tables\Table; use
    Illuminate\Database\Eloquent\Builder; use Illuminate\Database\Eloquent\SoftDeletingScope; use
    Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager; final class ArticleResource extends Resource {
    protected static ?string $model = Article::class; protected static string|BackedEnum|null $navigationIcon =
    Heroicon::OutlinedPencilSquare; protected static ?string $recordTitleAttribute = 'title'; public static function
    getModelLabel(): string { return __('Article'); } public static function getPluralModelLabel(): string { return
    __('Articles'); } public static function getNavigationSort(): ?int { $navigationSort =
    config('local-articles.navigation_sort'); return is_int($navigationSort) ? $navigationSort : null; } public static
    function getNavigationGroup(): ?string { $navigationGroup = config('local-articles.navigation_group'); return
    is_string($navigationGroup) ? $navigationGroup : null; } public static function form(Schema $schema): Schema { return
    ArticleForm::configure($schema); } public static function table(Table $table): Table { return
    ArticlesTable::configure($table); } public static function getRelations(): array { return [
    AuditsRelationManager::class, ]; } public static function getPages(): array { return [ 'index' =>
    ListArticles::route('/'), 'create' => CreateArticle::route('/create'), 'edit' =>
    EditArticle::route('/{record}/edit'), ]; } public static function getRecordRouteBindingEloquentQuery(): Builder {
    return parent::getRecordRouteBindingEloquentQuery() ->withoutGlobalScopes([ SoftDeletingScope::class, ]); } }
@endboostsnippet

- /src/Services/ArticleService.php serviço do resource de articles usado quando precisamos de regras de negócio
específicas no caso abaixo, para obter a lista de tags únicas já cadastradas e utilizarmos no formulário e tabela.
Declare no PHPDoc o tipo da Collection (`Collection<string, string>`) e do builder (`Builder<Article>`). Para estreitar
os valores, filtre com uma arrow function que checa o tipo (`->filter(fn (mixed $tag): bool => is_string($tag))`), que
o PHPStan entende; valores lidos do config seguem a mesma regra

@boostsnippet('Example content of ArticleService', 'php')
    declare(strict_types=1); namespace Agenciafmd\Articles\Services; use Agenciafmd\Articles\Models\Article; use
    Illuminate\Database\Eloquent\Builder; use Illuminate\Support\Collection; final class ArticleService { public static
    function make(): static { return resolve(self::class); } /** @return Collection<string, string> */ public function
    tags(): Collection { return $this->queryBuilder() ->pluck('tags') ->filter(static fn (mixed $tags): bool =>
    is_array($tags)) ->flatten() ->filter(static fn (mixed $tag): bool => is_string($tag)) ->unique()
    ->mapWithKeys(static fn (string $tag): array => [$tag => $tag]) ->sort(); } /** @return Builder<Article> */ private
    function queryBuilder(): Builder { return Article::query(); } }
@endboostsnippet

- /src/ArticlesPlugin.php classe principal do pacote aqui registramos o resource no painel administrativo (admix)

@boostsnippet('Example content of ArticlesPlugin', 'php')
    declare(strict_types=1); namespace Agenciafmd\Articles; use Agenciafmd\Articles\Resources\Articles\ArticleResource;
    use Filament\Contracts\Plugin; use Filament\Panel; final class ArticlesPlugin implements Plugin { public static
    function make(): static { return resolve(self::class); } public function getId(): string { return 'articles'; }
    public function register(Panel $panel): void { $panel ->resources([ ArticleResource::class, ]); } public function
    boot(Panel $panel): void { // } }
@endboostsnippet

- /tests/Feature os testes do pacote ficam dentro do próprio pacote, espelhando o caminho da classe em `/src` (ex.:
`/src/Services/ArticleService.php` vira `/tests/Feature/Services/ArticleServiceTest.php`); a suíte `Packages` do
`phpunit.xml` do projeto encontra os testes sozinha. Cada arquivo declara o namespace
`Agenciafmd\Articles\Tests\Feature\...` e `uses(TestCase::class, RefreshDatabase::class)`, porque o `tests/Pest.php`
do projeto só vale para a pasta `tests/`

@boostsnippet('Example content of ArticleServiceTest', 'php')
    declare(strict_types=1); namespace Agenciafmd\Articles\Tests\Feature\Services; use
    Agenciafmd\Articles\Models\Article; use Agenciafmd\Articles\Services\ArticleService; use
    Illuminate\Foundation\Testing\RefreshDatabase; use Illuminate\Support\Facades\Storage; use Tests\TestCase;
    uses(TestCase::class, RefreshDatabase::class); it('lists each tag once and in alphabetical order', function ():
    void { Storage::fake(); Article::factory()->create(['tags' => ['Projetos', 'Economia']]);
    Article::factory()->create(['tags' => ['Economia']]); expect(ArticleService::make()->tags()->all())->toBe([
    'Economia' => 'Economia', 'Projetos' => 'Projetos', ]); });
@endboostsnippet
