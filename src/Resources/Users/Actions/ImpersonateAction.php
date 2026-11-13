<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Users\Actions;

use Agenciafmd\Admix\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Livewire\Component;

/**
 * Lets administrators access the panel as another user, to see what the user sees.
 */
final class ImpersonateAction
{
    public static function make(): Action
    {
        return Action::make('impersonate')
            ->label(__('Impersonate'))
            ->icon(Heroicon::OutlinedArrowRightEndOnRectangle)
            ->visible(static fn (User $record): bool => ($user = auth()->user()) instanceof User && $user->canImpersonate($record))
            ->action(static function (User $record, Component $livewire): void {
                $user = auth()->user();

                if (! $user instanceof User) {
                    return;
                }

                $user->impersonate($record);

                $livewire->redirect(Filament::getUrl(Filament::hasTenancy() ? Filament::getUserDefaultTenant($record) : null));
            });
    }
}
