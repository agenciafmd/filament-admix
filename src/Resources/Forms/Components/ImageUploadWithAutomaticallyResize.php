<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Forms\Components;

use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Utilities\Get;

final class ImageUploadWithAutomaticallyResize
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
        return FileUploadWithDefault::make(
            name: $name,
            directory: $directory,
            fileNameField: $fileNameField,
        )
            ->downloadable()
            ->openable()
            ->image()
            ->automaticallyResizeImagesMode('cover') // Options: 'cover', 'contain', 'force'
            ->automaticallyResizeImagesToWidth($width)
            ->automaticallyResizeImagesToHeight($height)
            ->imageEditor(false)
            ->afterLabel(static function (Get $get) use ($width, $height): string {
                $resolvedWidth = $width instanceof Closure ? $width($get) : $width;
                $resolvedHeight = $height instanceof Closure ? $height($get) : $height;

                return "Max. {$resolvedWidth}x{$resolvedHeight}";
            })
            ->optimize(format: $format, quality: $quality);
    }
}
