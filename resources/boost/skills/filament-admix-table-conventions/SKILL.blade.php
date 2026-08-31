---
name: filament-admix-table-conventions
description: 'Use this skill whenever creating or editing the Table class of a Filament Resource inside an admix `local-{plural}` package — columns, filters, record/bulk actions, and `defaultSort` via the `WithScopes` `sort()` scope. Do not use it for Form/Schema work (see `filament-admix-form-fields`) or for scaffolding a whole new package (see `creating-filament-admix-package`).'
license: MIT
metadata:
    author: agenciafmd
---

# Table do resource admix - /src/Resources/Articles/Tables/ArticlesTable.php tabela do resource de articles a listagem
principal dos campos, quando disponíveis, são: title ou name, published_at, star e is_active os filtros principais,
quando disponíveis, são: is_active, star, tags e published_at na ação padrão de ordenação (defaultSort), utilize o scope
`sort` da trait `WithScopes` (`$query->sort()`) — ele já ordena pelos campos definidos em `$defaultSort` no Model (veja
a skill `creating-filament-admix-package`) o `BulkActionGroup`, deve conter `DeleteBulkAction::make()`,
`ForceDeleteBulkAction::make()` e `RestoreBulkAction::make()`

@boostsnippet('Example content of ArticlesTable', 'php')
    declare(strict_types=1); namespace Agenciafmd\Articles\Resources\Articles\Tables; use
    Agenciafmd\Articles\Services\ArticleService; use Filament\Actions\BulkActionGroup; use
    Filament\Actions\DeleteBulkAction; use Filament\Actions\EditAction; use Filament\Actions\ForceDeleteBulkAction; use
    Filament\Actions\RestoreBulkAction; use Filament\Forms\Components\DateTimePicker; use
    Filament\Tables\Columns\TextColumn; use Filament\Tables\Columns\ToggleColumn; use Filament\Tables\Filters\Filter;
    use Filament\Tables\Filters\SelectFilter; use Filament\Tables\Filters\TernaryFilter; use
    Filament\Tables\Filters\TrashedFilter; use Filament\Tables\Table; use Illuminate\Database\Eloquent\Builder; final
    class ArticlesTable { public static function configure(Table $table): Table { return $table ->columns([
    TextColumn::make('title') ->translateLabel() ->sortable() ->searchable(), TextColumn::make('published_at')
    ->translateLabel() ->dateTime(config('filament-admix.timestamp.format')) ->sortable(), ToggleColumn::make('star')
    ->translateLabel() ->sortable(), ToggleColumn::make('is_active') ->translateLabel() ->sortable(), ]) ->filters([
    TernaryFilter::make('is_active') ->translateLabel(), TernaryFilter::make('star') ->translateLabel(),
    SelectFilter::make('tags') ->translateLabel() ->options(fn (): array => ArticleService::make() ->tags() ->toArray())
    ->query(function (Builder $query, array $data): Builder { return $query->when($data['value'], fn (Builder $query,
    $value): Builder => $query->whereJsonContains('tags', $value)); }), Filter::make('published_at') ->schema([
    DateTimePicker::make('published_from') ->translateLabel(), DateTimePicker::make('published_until')
    ->translateLabel(), ]) ->query(function (Builder $query, array $data): Builder { return $query ->when(
    $data['published_from'], fn (Builder $query, $date): Builder => $query->whereDate('published_at', '>=', $date), )
    ->when( $data['published_until'], fn (Builder $query, $date): Builder => $query->whereDate('published_at', '<=',
    $date), ); }), TrashedFilter::make(), ]) ->recordActions([ EditAction::make(), ]) ->toolbarActions([
    BulkActionGroup::make([ DeleteBulkAction::make(), ForceDeleteBulkAction::make(), RestoreBulkAction::make(), ]), ])
    ->defaultSort(fn (Builder $query): Builder => $query->sort()); } }
@endboostsnippet

# RelationManager - /src/Resources/Articles/RelationManagers/CommentsRelationManager.php

um RelationManager é uma tabela: as convenções acima valem todas aqui, inclusive o `defaultSort` com o scope `sort()`.

o que muda:

- o nome da classe é o nome do relacionamento + `RelationManager` (`comments()` gera
`CommentsRelationManager`, `favoritedBy()` gera `FavoritedByRelationManager`) e é ele que vai em
`protected static string $relationship`
- o arquivo vive no pacote do **model que a tabela lista**, não no pacote do resource que registra o
RelationManager. Numa relação entre dois pacotes, isso significa que cada lado hospeda o
RelationManager do outro: `local-franchisees` tem o `FavoritedByRelationManager` (lista franqueados,
registrado no `RecipeResource`) e `local-recipes` tem o `FavoriteRecipesRelationManager` (lista
gelatos, registrado no `FranchiseeResource`)
- o título vem de `getTitle()` com `__()`, não do nome do relacionamento
- o registro é em `getRelations()` do resource, sempre antes do `AuditsRelationManager::class`

## RelationManager só leitura

quando o vínculo é criado fora do painel (pelo app, por exemplo), o RelationManager é só leitura. duas
camadas, porque uma só não basta:

- nenhuma action registrada — sem `headerActions`, `recordActions` nem `toolbarActions`
- `isReadOnly(): true` com o atributo `#[Override]`, que faz o Filament negar
attach/detach/associate/dissociate/create/edit/delete mesmo se alguém adicionar a action mais tarde

use `IconColumn::make('is_active')->boolean()` em vez de `ToggleColumn` — o toggle grava no registro.

coluna de campo do pivot (`created_at` do `withTimestamps()`, por exemplo) precisa de state explícito,
porque o pivot não é atributo do model listado.

@boostsnippet('Example content of FavoritedByRelationManager', 'php')
declare(strict_types=1); namespace Agenciafmd\Franchisees\Resources\Franchisees\RelationManagers; use
Agenciafmd\Franchisees\Models\Franchisee; use Carbon\CarbonInterface; use
Filament\Resources\RelationManagers\RelationManager; use Filament\Tables\Columns\IconColumn; use
Filament\Tables\Columns\TextColumn; use Filament\Tables\Table; use Illuminate\Database\Eloquent\Builder; use
Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\Pivot; use Override; final class
FavoritedByRelationManager extends RelationManager { protected static string $relationship = 'favoritedBy'; public
static function getTitle(Model $ownerRecord, string $pageClass): string { return __('Favorites'); } #[Override]
public function isReadOnly(): bool { return true; } public function table(Table $table): Table { return $table
->columns([ TextColumn::make('name') ->translateLabel() ->sortable() ->searchable(),
TextColumn::make('favorited_at') ->label(__('Favorited at')) ->state(function (Franchisee $record): ?CarbonInterface
{ $pivot = $record->getRelation('pivot'); return $pivot instanceof Pivot ? $pivot->created_at : null; })
->dateTime(config('filament-admix.timestamp.format')), IconColumn::make('is_active') ->translateLabel() ->boolean()
->sortable(), ]) ->defaultSort(fn (Builder $query): Builder => $query->sort()); } }
@endboostsnippet
