<?php

declare(strict_types=1);

use Filament\Support\Colors\Color;

return [
    'schedule' => [
        'minutes' => sprintf('%02d', abs(crc32((string) env('APP_NAME', 'FMD'))) % 60),
    ],
    'timestamp' => [
        'format' => env('ADMIX_TIMESTAMP_FORMAT', 'd/m/Y H:i:s'),
    ],
    /*
     * Local environment only: logs in automatically on the admix panel.
     * Use an e-mail to log in as that user, or true for the first active administrator.
     */
    'auto_login' => env('ADMIX_AUTO_LOGIN', true),
    'plugins' => [
        //        ArticlesPlugin::class,
    ],
    'colors' => [
        'primary' => Color::Slate,
    ],
    'font' => 'Ubuntu Sans',
];
