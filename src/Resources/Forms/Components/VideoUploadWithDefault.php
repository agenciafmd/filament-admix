<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Forms\Components;

use Filament\Forms\Components\FileUpload;

final class VideoUploadWithDefault
{
    public static function make(
        string $name,
        string $directory,
        string $fileNameField = 'name',
    ): FileUpload
    {
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
