<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Exports\Concerns;

use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

trait DefaultNotificationAndFileName
{
    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = self::translate('Your :model export has completed and :count :rows exported.', [
            'model' => str(self::translate(str(self::$model)
                ->afterLast('\\')
                ->plural()
                ->ucfirst()
                ->toString()))
                ->lower()
                ->toString(),
            'count' => self::formatCount($export->successful_rows),
            'rows' => self::translate(str('row')
                ->plural($export->successful_rows)
                ->toString()),
        ]);

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . self::translate(':count :rows failed to export.', [
                'count' => self::formatCount($failedRowsCount),
                'rows' => self::translate(str('row')
                    ->plural($failedRowsCount)
                    ->toString()),
            ]);
        }

        return $body;
    }

    public function getFileName(Export $export): string
    {
        return now()->format('YmdHis') . '-' . str(self::$model)
            ->afterLast('\\')
            ->lower()
            ->plural() . '-' . $export->id;
    }

    /**
     * @param  array<string, string>  $replace
     */
    private static function translate(string $key, array $replace = []): string
    {
        $translation = __($key, $replace);

        return is_string($translation) ? $translation : $key;
    }

    private static function formatCount(int $count): string
    {
        return Number::format($count) ?: (string) $count;
    }
}
