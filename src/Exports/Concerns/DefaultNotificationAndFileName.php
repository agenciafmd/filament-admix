<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Exports\Concerns;

use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

trait DefaultNotificationAndFileName
{
    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = __('Your :model export has completed and :count :rows exported.', [
            'model' => str(__(str(self::$model)
                ->afterLast('\\')
                ->plural()
                ->ucfirst()
                ->toString()))->lower(),
            'count' => Number::format($export->successful_rows),
            'rows' => __(str('row')
                ->plural($export->successful_rows)
                ->toString()),
        ]);

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . __(':count :rows failed to export.', [
                'count' => Number::format($failedRowsCount),
                'rows' => __(str('row')->plural($failedRowsCount)),
            ]);
        }

        return $body;
    }

    public function getFileName(Export $export): string
    {
        return now()->format('YmdHis') . '-' . str(self::$model)
            ->afterLast('\\')
            ->lower()
            ->plural() . '-' . $export->getKey();
    }
}
