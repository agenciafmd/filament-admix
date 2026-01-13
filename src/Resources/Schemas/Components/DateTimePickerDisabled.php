<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Schemas\Components;

use Filament\Forms\Components\DateTimePicker;
use Filament\Support\Enums\Operation;

final class DateTimePickerDisabled
{
    public static function make(string $name): DateTimePicker
    {
        return DateTimePicker::make($name)
            ->translateLabel()
            ->disabled()
            ->hiddenOn(Operation::Create);
    }
}
