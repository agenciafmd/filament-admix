<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Users\Exports;

use Agenciafmd\Admix\Exports\Concerns\DefaultNotificationAndFileName;
use Agenciafmd\Admix\Models\User;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;

final class UserExporter extends Exporter
{
    use DefaultNotificationAndFileName;

    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('fields.id')),
            ExportColumn::make('is_active')
                ->label(__('fields.is_active'))
                ->formatStateUsing(function (string $state): string {
                    return match ($state) {
                        '1' => __('Yes'),
                        '' => __('No'),
                    };
                }),
            ExportColumn::make('name')
                ->label(__('fields.name')),
            ExportColumn::make('email')
                ->label(__('fields.email')),
            ExportColumn::make('created_at')
                ->label(__('fields.created_at')),
            ExportColumn::make('updated_at')
                ->label(__('fields.updated_at')),
        ];
    }
}
