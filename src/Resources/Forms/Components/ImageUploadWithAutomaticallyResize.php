<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Forms\Components;

use Closure;
use Filament\Forms\Components\FileUpload;

final class ImageUploadWithAutomaticallyResize
{
    public static function make(
        string $name,
        string $directory,
        string $fileNameField = 'name',
        int|string|Closure $width = 1920,
        int|string|Closure $height = 1080,
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
            ->automaticallyResizeImagesToWidth(self::dimension($width))
            ->automaticallyResizeImagesToHeight(self::dimension($height))
            ->imageEditor(false)
            ->afterLabel(static fn (FileUpload $component): string => "Max. {$component->getAutomaticallyResizeImagesWidth()}x{$component->getAutomaticallyResizeImagesHeight()}");

        $upload->optimize(format: $format, quality: $quality);

        return $upload;
    }

    /**
     * O Filament só aceita `string|Closure`; closures são avaliadas por ele, com injeção de `Get`, `$record` etc.
     */
    private static function dimension(int|string|Closure $dimension): string|Closure
    {
        return is_int($dimension) ? (string) $dimension : $dimension;
    }
}
