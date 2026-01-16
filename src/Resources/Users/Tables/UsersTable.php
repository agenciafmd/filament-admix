<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Users\Tables;

use Agenciafmd\Admix\Models\User;
//use Agenciafmd\Admix\Resources\Users\Exports\UserExporter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
//use Filament\Actions\ExportBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->translateLabel()
                    ->searchable(),
                TextColumn::make('email')
                    ->translateLabel()
                    ->searchable(),
                ToggleColumn::make('is_active')
                    ->translateLabel()
                    ->disabled(fn (User $record) => auth()
                        ->user()
                        ->is($record)),
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
//                    ExportBulkAction::make()
//                        ->exporter(UserExporter::class),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort(function (Builder $query): Builder {
                return $query->orderBy('is_active', 'desc')
                    ->orderBy('name');
            });
    }
}
