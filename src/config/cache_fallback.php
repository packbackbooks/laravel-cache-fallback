<?php

return [
    'fallback_order' => [
        'redis',
        'memcached',
        'database',
        'cookie',
        'file',
        'array'
    ],
    'attempts_before_fallback' => 3,
    'interval_between_attempts' => 20, // in milliseconds
];
