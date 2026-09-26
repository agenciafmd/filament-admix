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
        $upload = FileUploadWithDefault::make(
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
            ->afterLabel(static fn (Get $get): string => 'Max. ' . self::resolveDimension($width, $get) . 'x' . self::resolveDimension($height, $get));

        $upload->optimize(format: $format, quality: $quality);

        return $upload;
    }

    private static function resolveDimension(string|Closure $dimension, Get $get): string
    {
        $value = $dimension instanceof Closure ? $dimension($get) : $dimension;

        return is_scalar($value) ? (string) $value : '';
    }
}
