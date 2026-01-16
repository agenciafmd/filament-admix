<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Schemas\Components;

use Agenciafmd\Support\Helper;
use Agenciafmd\Support\Rules\YouTubeUrl;
use Filament\Forms\Components\TextInput;

final class YouTubeInput
{
    public static function make(string $name = 'video'): TextInput
    {
        return TextInput::make($name)
            ->label(__('YouTube Video URL'))
            ->rule(new YouTubeUrl())
            ->live(onBlur: true) // Re-renders/processes on blur
            ->dehydrateStateUsing(function (?string $state): string {
                $state = Helper::sanitizeYoutube($state);

                return $state ?? '';
            })
            ->belowContent('Ex. https://youtu.be/aZ6cPPL3QEU');
    }
}
