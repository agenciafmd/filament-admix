<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Forms\Components;

use Guava\IconPicker\Forms\Components\IconPicker;

final class IconPickerWithDefault
{
    public static function make(string $name = 'icon'): IconPicker
    {
        return IconPicker::make($name)
            ->translateLabel()
            ->iconsSearchResults()
            ->iconsSearchResults(false)
            ->sets([
                'heroicons',
                'tabler',
                'frontend',
            ]);
    }
}
