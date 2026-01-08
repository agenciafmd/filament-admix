<?php

declare(strict_types=1);

return [
    'schedule' => [
        'minutes' => mb_substr(base_convert(config('app.name'), 36, 5), 0, 2),
    ],
];
