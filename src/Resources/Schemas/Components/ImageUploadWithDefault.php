<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Schemas\Components;

use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Utilities\Get;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

final class ImageUploadWithDefault
{
    public static function make(
        string $name,
        string $directory,
        string $fileNameField = 'name',
    ): FileUpload {
        return FileUpload::make($name)
            ->translateLabel()
            ->directory("media/{$directory}")
            ->getUploadedFileNameForStorageUsing(
                fn (TemporaryUploadedFile $file, Get $get): string => str($get($fileNameField))
                    ->trim()
                    ->append('-' . date('YmdHis') . '-' . random_int(1000, 9999))
                    ->slug() . '.' . str($file->getClientOriginalExtension())->lower(),
            )
            ->image()
            ->imageEditorAspectRatioOptions([
                '21:9',
                '16:9',
                '4:3',
                '1:1',
            ])
            ->imageEditorViewportWidth(1920)
            ->imageEditorViewportHeight(1080)
            ->imageEditor()
            ->maxSize(1024 * 10)
            ->columnSpanFull();
    }
}
