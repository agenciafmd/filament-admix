<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Infolists\Components;

use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\Operation;

final class DateTimeEntry
{
    public static function make(string $name): TextEntry
    {
        return TextEntry::make($name)
            ->translateLabel()
            ->date(config('filament-admix.timestamp.format'))
            ->hiddenOn(Operation::Create);
    }
}
