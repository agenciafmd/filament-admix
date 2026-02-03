<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Forms\Components;

use Filament\Forms\Components\FileUpload;

final class ImageUploadMultipleWithDefault
{
    public static function make(
        string $name,
        string $directory,
        string $fileNameField = 'name',
    ): FileUpload {
        return ImageUploadWithDefault::make(
            name: $name,
            directory: $directory,
            fileNameField: $fileNameField
        )
            ->multiple()
            ->panelLayout('grid')
            ->reorderable()
            ->appendFiles();
    }
}
