<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Forms\Components;

use Closure;
use Filament\Forms\Components\FileUpload;

final class ImageUploadMultipleWithAutomaticallyResize
{
    public static function make(
        string $name,
        string $directory,
        string $fileNameField = 'name',
        string|Closure $width = '1920',
        string|Closure $height = '1080',
        string $format = 'jpg',
        int $quality = 95,
    ): FileUpload {
        return ImageUploadWithAutomaticallyResize::make(
            name: $name,
            directory: $directory,
            fileNameField: $fileNameField,
            width: $width,
            height: $height,
            format: $format,
            quality: $quality,
        )
            ->multiple()
            ->panelLayout('grid')
            ->reorderable()
            ->appendFiles();
    }
}
