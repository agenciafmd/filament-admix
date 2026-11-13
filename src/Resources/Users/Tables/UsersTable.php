<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Users\Tables;

use Agenciafmd\Admix\Models\Organization;
use Agenciafmd\Admix\Models\User;
use Agenciafmd\Admix\Resources\Users\Actions\ImpersonateAction;
use Agenciafmd\Admix\Resources\Users\UserResource;
// use Agenciafmd\Admix\Resources\Users\Exports\UserExporter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
// use Filament\Actions\ExportBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

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
                TextColumn::make('role.name')
                    ->label(__('Role'))
                    ->placeholder(__('Administrator'))
                    ->sortable(),
                TextColumn::make('organizations.name')
                    ->label(__('Organizations'))
                    ->badge()
                    ->visible(fn (): bool => Organization::isTenancyEnabled() && auth()->user() instanceof User && auth()->user()->isAdmin()),
                ToggleColumn::make('is_active')
                    ->translateLabel()
                    ->disabled(fn (User $record): bool => $record->is(auth()->user()) || Gate::denies('update', $record)),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->translateLabel(),
                SelectFilter::make('role_id')
                    ->label(__('Role'))
                    ->relationship('role', 'name')
                    ->preload(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ImpersonateAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //                    ExportBulkAction::make()
                    //                        ->exporter(UserExporter::class),
                    DeleteBulkAction::make()
                        ->using(fn (Collection $records): Collection => $records->each(static function (Model $record): void {
                            if ($record instanceof User) {
                                UserResource::deleteRecord($record);
                            }
                        })),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort(fn (Builder $query): Builder => $query->sort());
    }
}
