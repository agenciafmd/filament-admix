<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Forms\Components;

use Agenciafmd\Support\Jobs\OptimizeVideo;
use Closure;
use Filament\Forms\Components\FileUpload;
use InvalidArgumentException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

final class VideoUploadWithAutomaticallyResize
{
    /**
     * O vídeo é salvo como enviado e convertido em segundo plano pelo {@see OptimizeVideo},
     * no tamanho exato informado (amplia e corta o excedente, centralizado). Com `mute`, a trilha de áudio é removida.
     */
    public static function make(
        string $name,
        string $directory,
        string $fileNameField = 'name',
        int|Closure $width = 1440,
        int|Closure $height = 810,
        bool $mute = true,
    ): FileUpload {
        return FileUploadWithDefault::make(
            name: $name,
            directory: $directory,
            fileNameField: $fileNameField,
        )
            ->acceptedFileTypes([
                'video/mp4',
            ])
            ->afterLabel(static fn (FileUpload $component): string => 'Max. ' . self::dimension($component, $width) . 'x' . self::dimension($component, $height))
            ->saveUploadedFileUsing(static function (FileUpload $component, TemporaryUploadedFile $file) use ($width, $height, $mute): ?string {
                $path = $component->saveUploadedFile($file);

                OptimizeVideo::dispatchIf(
                    filled($path) && config()->boolean('filament-admix.fileupload_video_optimize', true),
                    $component->getDiskName(),
                    (string) $path,
                    self::dimension($component, $width),
                    self::dimension($component, $height),
                    $mute,
                );

                return $path;
            });
    }

    /**
     * Closures são avaliadas pelo Filament, com injeção de `Get`, `$record` etc.
     */
    private static function dimension(FileUpload $component, int|Closure $dimension): int
    {
        $value = $component->evaluate($dimension);

        throw_unless(is_numeric($value), InvalidArgumentException::class, 'The video dimensions must be numeric.');

        return (int) $value;
    }
}
