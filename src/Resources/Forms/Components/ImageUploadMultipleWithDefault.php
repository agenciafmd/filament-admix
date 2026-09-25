<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Forms\Components;

use Deprecated;
use Filament\Forms\Components\FileUpload;

/**
 * @deprecated Use {@see ImageUploadMultipleWithAutomaticallyResize} instead.
 */
final class ImageUploadMultipleWithDefault
{
    #[Deprecated(message: 'use ImageUploadMultipleWithAutomaticallyResize::make() instead')]
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
