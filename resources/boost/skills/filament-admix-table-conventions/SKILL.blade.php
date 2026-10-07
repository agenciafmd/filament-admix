---
name: filament-admix-table-conventions
description: 'Use this skill whenever creating or editing the Table class of a Filament Resource inside an admix `local-{plural}` package — columns, filters, record/bulk actions, reorderable tables, RelationManagers, and `defaultSort` via the `WithScopes` `sort()` scope. Do not use it for Form/Schema work (see `filament-admix-form-fields`) or for scaffolding a whole new package (see `creating-filament-admix-package`).'
license: MIT
metadata:
    author: agenciafmd
---

# Table do resource admix

## /src/Resources/Articles/Tables/ArticlesTable.php

Tabela do resource de articles.

### Colunas

- as colunas principais, quando disponíveis, são: `title` ou `name`, `published_at`, `star` e `is_active`
- o formato de data das colunas vem de `config()->string('filament-admix.timestamp.format', 'd/m/Y H:i:s')`
- coluna de tags ou de relação (`categories.name`) usa `->badge()->limitList(3)->expandableLimitedList()`
- coluna de contagem de arquivos (json) usa `->state(fn (Material $record): int => count($record->files ?? []))`

### Filtros

A ordem dos filtros é:

1. `is_active`
2. filtros específicos do pacote (relações, enums, campos próprios)
3. `star`
4. `tags`
5. `published_at`
6. `TrashedFilter`

Os valores dos filtros (`$data`) chegam como `mixed`: leia cada um pelo helper privado `filterValue()`, que devolve
`string|null`, e tipe o valor na closure do `when()` (`string $value`, `string $date`).

### Actions

- o `BulkActionGroup` deve conter `DeleteBulkAction::make()`, `ForceDeleteBulkAction::make()` e
  `RestoreBulkAction::make()`
- as actions padrão (Edit, Delete, ForceDelete, Restore) e as colunas editáveis (`ToggleColumn` etc.) já respeitam as
  permissões do Grupo do usuário; uma action própria (`Action::make('send')`) precisa de `->authorize('send')` e da
  ability declarada em `getExtraPermissions()` no Resource — veja a skill `filament-admix-permissions`
- ao chamar `->disabled()` numa coluna editável, inclua `Gate::denies('update', $record)` na condição, pois ela
  sobrescreve o padrão

### Ordenação

Na ordenação padrão (`defaultSort`), utilize o scope `sort` da trait `WithScopes` (`$query->sort()`); ele já ordena
pelos campos definidos em `$defaultSort` no Model (veja a skill `creating-filament-admix-package`). O `$query->sort()` é
reconhecido pelo PHPStan por uma extensão do admix, então não precisa de anotação.

@boostsnippet('Example content of ArticlesTable', 'php')
<?php

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
                TextColumn::make('tags')
                    ->translateLabel()
                    ->badge()
                    ->limitList(3)
                    ->expandableLimitedList(),
                TextColumn::make('published_at')
                    ->translateLabel()
                    ->dateTime(config()->string('filament-admix.timestamp.format', 'd/m/Y H:i:s'))
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
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        self::filterValue($data, 'value'),
                        fn (Builder $query, string $value): Builder => $query->whereJsonContains('tags', $value),
                    )),
                Filter::make('published_at')
                    ->schema([
                        DateTimePicker::make('published_from')
                            ->translateLabel(),
                        DateTimePicker::make('published_until')
                            ->translateLabel(),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            self::filterValue($data, 'published_from'),
                            fn (Builder $query, string $date): Builder => $query->whereDate('published_at', '>=', $date),
                        )
                        ->when(
                            self::filterValue($data, 'published_until'),
                            fn (Builder $query, string $date): Builder => $query->whereDate('published_at', '<=', $date),
                        )),
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

    /**
     * @param  array<array-key, mixed>  $data
     */
    private static function filterValue(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
@endboostsnippet

## Tabela reordenável

Quando o Model tem a coluna `sort` e a ordem é definida arrastando as linhas:

- `->reorderable('sort')` com o botão de reordenar nomeado pelo `reorderRecordsTriggerAction()`
- o `defaultSort` continua pelo scope `sort()`, com `'sort' => 'asc'` no `$defaultSort` do Model
- as colunas **não** levam `->sortable()`: ordenar por outra coluna esconde a ordem que o usuário arrastou
- `sort` não vira coluna nem campo do formulário

@boostsnippet('Example content of a reorderable table', 'php')
return $table
    ->columns([
        TextColumn::make('name')
            ->translateLabel()
            ->searchable(),
        ToggleColumn::make('is_active')
            ->translateLabel(),
    ])
    // filters, recordActions, toolbarActions...
    ->reorderable('sort')
    ->reorderRecordsTriggerAction(
        fn (Action $action, bool $isReordering): Action => $action
            ->button()
            ->label($isReordering ? __('Disable reordering') : __('Enable reordering')),
    )
    ->defaultSort(fn (Builder $query): Builder => $query->sort());
@endboostsnippet

# RelationManager

## /src/Resources/Articles/RelationManagers/CommentsRelationManager.php

Um RelationManager é uma tabela: as convenções acima valem todas aqui, inclusive o `defaultSort` com o scope `sort()`.
O que muda:

- o nome da classe é o nome do relacionamento + `RelationManager` (`comments()` gera `CommentsRelationManager`,
  `favoritedBy()` gera `FavoritedByRelationManager`) e é ele que vai em `protected static string $relationship`
- o arquivo vive no pacote do **model que a tabela lista**, não no pacote do resource que registra o RelationManager.
  Numa relação entre dois pacotes, isso significa que cada lado hospeda o RelationManager do outro: `local-franchisees`
  tem o `FavoritedByRelationManager` (lista franqueados, registrado no `RecipeResource`) e `local-recipes` tem o
  `FavoriteRecipesRelationManager` (lista receitas, registrado no `FranchiseeResource`)
- o título vem de `getTitle()` com `__()` e é o nome da entidade listada (`__('Franchisees')`), não o nome do
  relacionamento
- o registro é em `getRelations()` do resource, sempre antes do `AuditsRelationManager::class`
- os métodos sobrescritos (`getTitle()`, `table()`, `isReadOnly()`) recebem `#[Override]`

## RelationManager só leitura

Quando o vínculo é criado fora do painel (pelo app, por exemplo), o RelationManager é só leitura. Duas camadas, porque
uma só não basta:

- nenhuma action registrada — sem `headerActions`, `recordActions` nem `toolbarActions`
- `isReadOnly(): true` com o atributo `#[Override]`, que faz o Filament negar
  attach/detach/associate/dissociate/create/edit/delete mesmo se alguém adicionar a action mais tarde

Use `IconColumn::make('is_active')->boolean()` em vez de `ToggleColumn` — o toggle grava no registro.

Coluna de campo do pivot (`created_at` do `withTimestamps()`, por exemplo) precisa de state explícito, porque o pivot
não é atributo do model listado. O `Pivot` não declara as colunas, então `$pivot->created_at` não passa no PHPStan: leia
com `$pivot->getAttribute('created_at')` e confira o tipo com `instanceof CarbonInterface`.

@boostsnippet('Example content of FavoritedByRelationManager', 'php')
<?php

declare(strict_types=1);

namespace Agenciafmd\Franchisees\Resources\Franchisees\RelationManagers;

use Agenciafmd\Franchisees\Models\Franchisee;
use Carbon\CarbonInterface;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Override;

final class FavoritedByRelationManager extends RelationManager
{
    protected static string $relationship = 'favoritedBy';

    #[Override]
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Franchisees');
    }

    #[Override]
    public function isReadOnly(): bool
    {
        return true;
    }

    #[Override]
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->translateLabel()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('units.name')
                    ->translateLabel()
                    ->badge()
                    ->limitList(3)
                    ->expandableLimitedList(),
                TextColumn::make('favorited_at')
                    ->label(__('Favorited at'))
                    ->state(function (Franchisee $record): ?CarbonInterface {
                        $pivot = $record->getRelation('pivot');

                        if (! $pivot instanceof Pivot) {
                            return null;
                        }

                        $favoritedAt = $pivot->getAttribute('created_at');

                        return $favoritedAt instanceof CarbonInterface
                            ? $favoritedAt
                            : null;
                    })
                    ->dateTime(config()->string('filament-admix.timestamp.format', 'd/m/Y H:i:s')),
                IconColumn::make('is_active')
                    ->translateLabel()
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort(fn (Builder $query): Builder => $query->sort());
    }
}
@endboostsnippet
