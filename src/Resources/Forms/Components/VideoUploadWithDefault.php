<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Forms\Components;

use Deprecated;
use Filament\Forms\Components\FileUpload;

/**
 * @deprecated Use {@see VideoUploadWithAutomaticallyResize} instead.
 */
final class VideoUploadWithDefault
{
    #[Deprecated(message: 'use VideoUploadWithAutomaticallyResize::make() instead')]
    public static function make(
        string $name,
        string $directory,
        string $fileNameField = 'name',
    ): FileUpload {
        return FileUploadWithDefault::make(
            name: $name,
            directory: $directory,
            fileNameField: $fileNameField,
        )
            ->acceptedFileTypes([
                'video/mp4',
            ]);
    }
}
