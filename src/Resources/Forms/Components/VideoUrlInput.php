<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Forms\Components;

use Agenciafmd\Support\Helper;
use Agenciafmd\Support\Rules\VideoUrl;
use Filament\Forms\Components\TextInput;

final class VideoUrlInput
{
    public static function make(string $name = 'video'): TextInput
    {
        return TextInput::make($name)
            ->label(__('Video URL (YouTube or Instagram Reel)'))
            ->rule(new VideoUrl())
            ->live(onBlur: true)
            ->dehydrateStateUsing(function (?string $state): string {
                $state = Helper::sanitizeVideo($state);

                return $state ?? '';
            });
    }
}
