---
name: creating-filament-admix-package
description: 'Use this skill whenever creating a brand-new `local-{plural}` package under `packages/agenciafmd/` for the Filament admix starter kit — config, factory, migration, seeder, translations, Model, ServiceProviders, Create/Edit/List Pages, Resource wiring, Service and Plugin. Do not use it just to tweak the Form schema or Table of an already-existing resource — see the `filament-admix-form-fields` and `filament-admix-table-conventions` skills for that.'
license: MIT
metadata:
    author: agenciafmd
---

# Criando um novo pacote admix

Os exemplos usam o pacote `local-articles`. Todo método que sobrescreve um método da classe pai (do Laravel ou do
Filament) recebe o atributo `#[Override]`; métodos que só implementam uma interface (ex.: os do `Plugin`) não.

## /config/local-articles.php

Configuração do pacote.

@boostsnippet('Example content of config/local-articles.php', 'php')
<?php

declare(strict_types=1);

return [
    'name' => 'Articles',
    'navigation_group' => null,
    'navigation_sort' => 6,
];
@endboostsnippet

## /database/factories/ArticleFactory.php

Fábrica de dados para inserirmos no banco.

@verbatim
- declare `/** @extends Factory<Article> */` e `protected $model = Article::class`: o Seeder usa `ArticleFactory::new()`,
  que sem `$model` procuraria `App\Models\Article`
- para o slug, passe o texto para o helper e converta para string (`str($title)->slug()->toString()`); sem o
  `->toString()` a factory grava um `Stringable`
@endverbatim

@boostsnippet('Example content of ArticleFactory', 'php')
<?php

declare(strict_types=1);

namespace Agenciafmd\Articles\Database\Factories;

use Agenciafmd\Articles\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Override;

/**
 * @extends Factory<Article>
 */
final class ArticleFactory extends Factory
{
    protected $model = Article::class;

    #[Override]
    public function definition(): array
    {
        $title = fake()->sentence(4);
        $slug = str($title)->slug()->toString();

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
                ->map(fn (): string|false => Storage::putFile('fake', fake()->localImage(ratio: '16:9')))
                ->all(),
            'slug' => $slug,
        ];
    }
}
@endboostsnippet

Utilize a relação de valores abaixo para os campos, caso sejam solicitados.

| campo        | padrão                                                                                                        |
|--------------|---------------------------------------------------------------------------------------------------------------|
| is_active    | `fake()->boolean()`                                                                                           |
| star         | `fake()->boolean()`                                                                                           |
| name         | `fake()->sentence(4)`                                                                                         |
| title        | `fake()->sentence(4)`                                                                                         |
| subtitle     | `fake()->sentence(8)`                                                                                         |
| author       | `fake()->firstName() . ' ' . fake()->lastName()`                                                              |
| summary      | `fake()->text()`                                                                                              |
| published_at | `fake()->dateTimeBetween(now()->subMonths(6), now()->addDay())`                                               |
| content      | `fake()->htmlParagraphs()`                                                                                    |
| description  | `fake()->htmlParagraphs()`                                                                                    |
| tags         | `fake()->tags()`                                                                                              |
| video        | `fake()->youtubeRandomUri()`                                                                                  |
| image        | `Storage::putFile('fake', fake()->localImage(ratio: '16:9'))`                                                 |
| images       | `collect(range(0, fake()->numberBetween(1, 6)))->map(fn () => Storage::putFile('fake', fake()->localImage(ratio: '16:9')))->all()` |
| slug         | `str($title)->slug()->toString()`                                                                             |

## /database/migrations/YYYY_MM_DD_HHMMSS_create_articles_table.php

- não utilize o método `down` e remova os doc blocks
- caso existam, separe as migrações em 1 arquivo por recurso ou tabela
- a closure do `Schema::create()` declara o retorno (`static function (Blueprint $table): void`), senão o
  `--type-coverage --min=100` falha
- adicione `->index()` nos booleanos que filtram a listagem do painel (`is_active`, `star`); um booleano que não é
  filtro nem ordenação (ex.: `is_selected` de uma pivot) não paga índice
- adicione `->nullable()` para os campos que não são obrigatórios
- adicione os campos `created_at`, `updated_at` e `deleted_at` com `$table->timestamps()` e `$table->softDeletes()`

@boostsnippet('Example content of create_articles_table migration', 'php')
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', static function (Blueprint $table): void {
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
};
@endboostsnippet

## /database/seeders/ArticleSeeder.php

Use `ArticleFactory::new()` e não `Article::factory()`: o Larastan não resolve o atributo `#[UseFactory]` do Model, e
`Article::factory()` vira `mixed` na análise. A mesma regra vale fora dos testes (comandos, outros seeders).

@boostsnippet('Example content of ArticleSeeder', 'php')
<?php

declare(strict_types=1);

namespace Agenciafmd\Articles\Database\Seeders;

use Agenciafmd\Articles\Database\Factories\ArticleFactory;
use Agenciafmd\Articles\Models\Article;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

final class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        Schema::withoutForeignKeyConstraints(fn () => Article::query()
            ->truncate());

        ArticleFactory::new()
            ->count(50)
            ->create();
    }
}
@endboostsnippet

## /lang/pt_BR/fields.php

@boostsnippet('Example content of fields', 'php')
<?php

declare(strict_types=1);

return [
    //
];
@endboostsnippet

## /lang/pt_BR.json

Traduções dos labels dos campos.

@boostsnippet('Example content of pt_BR.json', 'json')
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
@endboostsnippet

## /src/Models/Article.php

- não utilize o `$fillable`
- utilize a trait `WithScopes` (`Agenciafmd\Admix\Traits\WithScopes`) para os scopes `isActive` e `sort`; ela lê a
  propriedade `$defaultSort` do Model, que é obrigatória, então não reimplemente a ordenação
- a ordem de `$defaultSort`, quando disponíveis, segue os campos: `is_active`, `star`, `published_at` e `title` ou `name`
- o `prunable()` usa `today()` (e não `now()`), para que o corte seja sempre a meia-noite do dia

@verbatim
Tipagem esperada no Model:

- `/** @use HasFactory<ArticleFactory> */` no trait
- `@var array<string, 'asc'|'desc'>` no `$defaultSort`
- `@return Builder<self>` no `prunable()`
- `@return array<string, string>` no `casts()`
- accessors (`Attribute`) declaram `@return Attribute<Tipo, never>`; um accessor que devolve `RichContentRenderer` usa
  `'<p></p>'` quando o conteúdo estiver vazio, porque o renderer do Filament quebra com `null` ou string vazia
@endverbatim

@boostsnippet('Example of content of Article', 'php')
<?php

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
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;
    use Prunable;
    use SoftDeletes;
    use WithScopes;

    /**
     * @var array<string, 'asc'|'desc'>
     */
    protected array $defaultSort = [
        'is_active' => 'desc',
        'star' => 'desc',
        'published_at' => 'desc',
        'title' => 'asc',
    ];

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return self::query()
            ->where('deleted_at', '<=', today()->subDays(30));
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'star' => 'boolean',
            'tags' => 'array',
            'images' => 'array',
            'published_at' => 'datetime',
        ];
    }
}
@endboostsnippet

Utilize a relação de valores abaixo para os campos no `casts()`, caso sejam solicitados.

| campo        | padrão     |
|--------------|------------|
| is_active    | `boolean`  |
| star         | `boolean`  |
| tags         | `array`    |
| images       | `array`    |
| published_at | `datetime` |

Não use `timestamp` como cast de data: ele devolve `int`, e o `->dateTime()` da Table espera um Carbon.

## /src/Providers/ArticleServiceProvider.php

Responsável por registrar os recursos do pacote.

@boostsnippet('Example content of ArticleServiceProvider', 'php')
<?php

declare(strict_types=1);

namespace Agenciafmd\Articles\Providers;

use Illuminate\Support\ServiceProvider;
use Override;

final class ArticleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->bootProviders();

        $this->bootMigrations();

        $this->bootTranslations();
    }

    #[Override]
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
@endboostsnippet

## /src/Providers/CommandServiceProvider.php

Responsável por registrar os comandos e agendamentos do pacote. A closure do `booted()` declara o retorno
(`function (): void`).

@boostsnippet('Example content of CommandServiceProvider', 'php')
<?php

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

        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);
            $minutes = config()->string('filament-admix.schedule.minutes', '00');

            $schedule->command('model:prune', [
                '--model' => [
                    Article::class,
                ],
            ])->dailyAt("03:{$minutes}");
        });
    }
}
@endboostsnippet

## /src/Resources/Articles/Pages/CreateArticle.php

Registramos o resource de articles e aplicamos o trait `RedirectBack` para retornar para a lista após criar um novo
registro.

@boostsnippet('Example content of CreateArticle', 'php')
<?php

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
@endboostsnippet

## /src/Resources/Articles/Pages/EditArticle.php

- registramos o resource de articles e aplicamos o trait `RedirectBack` para retornar para a lista após salvar
- registramos o listener de `auditRestored` para atualizar o formulário após restaurar um registro da auditoria
- o `$listeners` recebe o PHPDoc com o tipo `array<int, string>`
- o `getRelationManagers()` confere o tipo do registro com `$this->getRecord()` antes de chamar `trashed()`, e esconde
  os RelationManagers de um registro excluído
- adicionamos no `getHeaderActions()` as ações `DeleteAction::make()`, `ForceDeleteAction::make()` e
  `RestoreAction::make()`

@boostsnippet('Example content of EditArticle', 'php')
<?php

declare(strict_types=1);

namespace Agenciafmd\Articles\Resources\Articles\Pages;

use Agenciafmd\Admix\Resources\Concerns\RedirectBack;
use Agenciafmd\Articles\Models\Article;
use Agenciafmd\Articles\Resources\Articles\ArticleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Override;

final class EditArticle extends EditRecord
{
    use RedirectBack;

    protected static string $resource = ArticleResource::class;

    /**
     * @var array<int, string>
     */
    protected $listeners = [
        'auditRestored',
    ];

    #[Override]
    public function getRelationManagers(): array
    {
        $record = $this->getRecord();

        if ($record instanceof Article && $record->trashed()) {
            return [];
        }

        return parent::getRelationManagers();
    }

    public function auditRestored(): void
    {
        $this->fillForm();
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
@endboostsnippet

## /src/Resources/Articles/Pages/ListArticles.php

Registramos o resource de articles e adicionamos no `getHeaderActions()` a ação de criar um novo registro
(`CreateAction::make()`).

@boostsnippet('Example content of ListArticles', 'php')
<?php

declare(strict_types=1);

namespace Agenciafmd\Articles\Resources\Articles\Pages;

use Agenciafmd\Articles\Resources\Articles\ArticleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

final class ListArticles extends ListRecords
{
    protected static string $resource = ArticleResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
@endboostsnippet

## /src/Resources/Articles/ArticleResource.php

- `getNavigationSort()` e `getNavigationGroup()` leem do config do pacote, permitindo reordenar/reagrupar o menu sem
  alterar código
  - `getNavigationSort(): int` usa `config()->integer('local-articles.navigation_sort')`, que o config sempre define
  - `getNavigationGroup(): ?string` mantém a guarda `is_string()`: o `navigation_group` pode ser `null`, e o
    `config()->string()` lança exceção com `null`
- `form()`/`table()` só delegam para as classes `ArticleForm`/`ArticlesTable`; veja as skills
  `filament-admix-form-fields` e `filament-admix-table-conventions` para montar o conteúdo delas
- `getRelations()` lista os RelationManagers do recurso, sempre com o `AuditsRelationManager::class` por último; os
  RelationManagers próprios do pacote ficam em `/src/Resources/Articles/RelationManagers/` e as convenções deles (nome,
  localização entre pacotes, modo só leitura) estão na skill `filament-admix-table-conventions`
- não crie Policy nem AuthServiceProvider: o admix registra a `ResourcePolicy` automaticamente e as permissões do
  Resource (visualizar, criar, atualizar, deletar, restaurar, auditoria) aparecem no formulário de Grupos; se o Resource
  tiver actions próprias (enviar, aprovar...), declare-as em `getExtraPermissions()` e proteja a action com
  `->authorize()` — veja a skill `filament-admix-permissions`

@boostsnippet('Example content of ArticleResource', 'php')
<?php

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
use Override;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

final class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?string $recordTitleAttribute = 'title';

    #[Override]
    public static function getModelLabel(): string
    {
        return __('Article');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return __('Articles');
    }

    #[Override]
    public static function getNavigationSort(): int
    {
        return config()->integer('local-articles.navigation_sort');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        $navigationGroup = config('local-articles.navigation_group');

        return is_string($navigationGroup) ? $navigationGroup : null;
    }

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return ArticleForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return ArticlesTable::configure($table);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            AuditsRelationManager::class,
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListArticles::route('/'),
            'create' => CreateArticle::route('/create'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
@endboostsnippet

## Resource aninhado (`$parentResource`)

Quando o registro só existe dentro de outro (aulas de um curso, por exemplo), o Resource filho declara
`protected static ?string $parentResource = CourseResource::class;` e fica em
`/src/Resources/Courses/Resources/Lessons/`. As rotas saem como `courses/{course}/lessons/...`.

- o filho **não** tem página `index`: a listagem é um RelationManager no Resource pai, com
  `protected static ?string $relatedResource = LessonResource::class;` e `table()` delegando para
  `LessonResource::table($table)`
- Create e Edit do filho **não** usam o `RedirectBack`: ele redireciona para `getResourceUrl('index')`, que não existe
  no filho. Deixe o redirecionamento padrão do Filament, que volta para o pai
- o resto continua igual a um Resource comum: `AuditsRelationManager::class` em `getRelations()`, o listener
  `auditRestored` e a guarda de `trashed()` em `getRelationManagers()` na Edit page, e as header actions de
  Delete/ForceDelete/Restore
- na matriz de permissões do Grupo, o filho ganha linha própria, sem o grupo de navegação do pai. O RelationManager do
  pai só aparece para quem tem a permissão de visualizar do filho — marque as duas

@boostsnippet('Example content of nested LessonResource pages', 'php')
#[Override]
public static function getPages(): array
{
    return [
        'create' => CreateLesson::route('/create'),
        'edit' => EditLesson::route('/{record}/edit'),
    ];
}
@endboostsnippet

## /src/Services/ArticleService.php

Serviço do resource de articles, usado quando precisamos de regras de negócio específicas. No caso abaixo, para obter
a lista de tags únicas já cadastradas e utilizarmos no formulário e na tabela.

- declare no PHPDoc o tipo da Collection (`Collection<string, string>`) e do builder (`Builder<Article>`)
- para estreitar os valores, filtre com uma arrow function que checa o tipo
  (`->filter(fn (mixed $tag): bool => is_string($tag))`), que o PHPStan entende; valores lidos do config seguem a
  mesma regra

@boostsnippet('Example content of ArticleService', 'php')
<?php

declare(strict_types=1);

namespace Agenciafmd\Articles\Services;

use Agenciafmd\Articles\Models\Article;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class ArticleService
{
    public static function make(): static
    {
        return resolve(self::class);
    }

    /**
     * @return Collection<string, string>
     */
    public function tags(): Collection
    {
        return $this->queryBuilder()
            ->pluck('tags')
            ->filter(static fn (mixed $tags): bool => is_array($tags))
            ->flatten()
            ->filter(static fn (mixed $tag): bool => is_string($tag))
            ->unique()
            ->mapWithKeys(static fn (string $tag): array => [$tag => $tag])
            ->sort();
    }

    /**
     * @return Builder<Article>
     */
    private function queryBuilder(): Builder
    {
        return Article::query();
    }
}
@endboostsnippet

## /src/ArticlesPlugin.php

Classe principal do pacote; aqui registramos o resource no painel administrativo (admix).

@boostsnippet('Example content of ArticlesPlugin', 'php')
<?php

declare(strict_types=1);

namespace Agenciafmd\Articles;

use Agenciafmd\Articles\Resources\Articles\ArticleResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

final class ArticlesPlugin implements Plugin
{
    public static function make(): static
    {
        return resolve(self::class);
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
@endboostsnippet

## /tests/Feature

Os testes do pacote ficam dentro do próprio pacote, espelhando o caminho da classe em `/src` (ex.:
`/src/Services/ArticleService.php` vira `/tests/Feature/Services/ArticleServiceTest.php`).

Pré-requisito no projeto: o `phpunit.xml` precisa da suíte `Packages`. Sem ela, os testes do pacote nunca rodam —
confira antes de escrever o primeiro teste e, se faltar, adicione:

@boostsnippet('Example of the Packages test suite in phpunit.xml', 'xml')
<testsuite name="Packages">
    <directory>packages/agenciafmd/*/tests</directory>
</testsuite>
@endboostsnippet

- cada arquivo declara o namespace `Agenciafmd\Articles\Tests\Feature\...` e `uses(TestCase::class, RefreshDatabase::class)`,
  porque o `tests/Pest.php` do projeto só vale para a pasta `tests/`
- crie os registros com `ArticleFactory::new()`, pelo mesmo motivo do Seeder (Larastan e `#[UseFactory]`)

@boostsnippet('Example content of ArticleServiceTest', 'php')
<?php

declare(strict_types=1);

namespace Agenciafmd\Articles\Tests\Feature\Services;

use Agenciafmd\Articles\Database\Factories\ArticleFactory;
use Agenciafmd\Articles\Services\ArticleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('lists each tag once and in alphabetical order', function (): void {
    Storage::fake();
    ArticleFactory::new()->create(['tags' => ['Projetos', 'Economia']]);
    ArticleFactory::new()->create(['tags' => ['Economia']]);

    expect(ArticleService::make()->tags()->all())->toBe([
        'Economia' => 'Economia',
        'Projetos' => 'Projetos',
    ]);
});
@endboostsnippet
