<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Forms\Components;

use Closure;
use Filament\Forms\Components\FileUpload;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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
        return FileUploadWithDefault::make(
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
            ->afterLabel(static fn (FileUpload $component): string => "Max. {$component->getAutomaticallyResizeImagesWidth()}x{$component->getAutomaticallyResizeImagesHeight()}")
            ->saveUploadedFileUsing(static fn (FileUpload $component, TemporaryUploadedFile $file): string => self::store($component, $file, $format, $quality));
    }

    /**
     * Amplia a imagem até o menor lado atingir o alvo e corta o excedente do maior lado, centralizado,
     * garantindo o tamanho exato mesmo quando o navegador não transformou o arquivo.
     */
    private static function store(FileUpload $component, TemporaryUploadedFile $file, string $format, int $quality): string
    {
        $image = new ImageManager(new Driver)
            ->read($file->getRealPath())
            ->cover(
                width: (int) $component->getAutomaticallyResizeImagesWidth(),
                height: (int) $component->getAutomaticallyResizeImagesHeight(),
            );

        $path = str($component->getUploadedFileNameForStorage($file))
            ->beforeLast('.')
            ->append(".{$format}")
            ->prepend(mb_trim((string) $component->getDirectory(), '/') . '/')
            ->ltrim('/')
            ->toString();

        $component->getDisk()->put($path, (string) $image->encodeByExtension($format, quality: $quality));

        return $path;
    }

    /**
     * O Filament só aceita `string|Closure`; closures são avaliadas por ele, com injeção de `Get`, `$record` etc.
     */
    private static function dimension(int|string|Closure $dimension): string|Closure
    {
        return is_int($dimension) ? (string) $dimension : $dimension;
    }
}
