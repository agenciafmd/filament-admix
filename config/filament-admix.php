<?php

declare(strict_types=1);

// use Agenciafmd\Articles\ArticlesPlugin;

return [
    'schedule' => [
        'minutes' => mb_substr(base_convert(env('APP_NAME'), 36, 5), 0, 2),
    ],
    'timestamp' => [
        'format' => env('ADMIX_TIMESTAMP_FORMAT', 'd/m/Y H:i:s'),
    ],
    'plugins' => [
        //        ArticlesPlugin::make(),
    ],
];
