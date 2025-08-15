<?php

return [
    'enabled' => true,
    'api' => [
        'key' => getenv('WEATHER_API_KEY'),
        'endpoint' => 'https://api.weatherapi.com/v1',
        'version' => '1.0'
    ],
    'defaults' => [
        'units' => 'metric', // or 'imperial'
        'language' => 'en',
        'location_type' => 'auto' // 'auto', 'zip', 'coordinates'
    ],
    'features' => [
        'current' => true,
        'forecast' => [
            'enabled' => true,
            'days' => 7
        ],
        'alerts' => true,
        'history' => [
            'enabled' => true,
            'max_days' => 30
        ]
    ],
    'caching' => [
        'enabled' => true,
        'ttl' => [
            'current' => 1800, // 30 minutes
            'forecast' => 3600, // 1 hour
            'history' => 86400 // 24 hours
        ]
    ],
    'rate_limiting' => [
        'requests_per_day' => 1000,
        'requests_per_minute' => 30,
        'retry_attempts' => 3,
        'retry_delay' => 1000 // milliseconds
    ]
];
