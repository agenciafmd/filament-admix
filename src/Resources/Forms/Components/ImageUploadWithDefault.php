<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Forms\Components;

use Filament\Forms\Components\FileUpload;

final class ImageUploadWithDefault
{
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
            ->image()
            ->optimize(format: 'jpg', quality: 95)
            ->imageEditorAspectRatioOptions([
                '21:9',
                '16:9',
                '4:3',
                '1:1',
            ])
            ->imageEditorViewportWidth(1920)
            ->imageEditorViewportHeight(1080)
            ->imageEditor();
    }
}
