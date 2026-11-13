<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Organizations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class OrganizationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->translateLabel()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('slug')
                    ->translateLabel()
                    ->searchable(),
                TextColumn::make('users_count')
                    ->label(__('Users'))
                    ->counts('users')
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->translateLabel()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->translateLabel(),
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
