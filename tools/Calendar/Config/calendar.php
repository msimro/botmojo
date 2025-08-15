<?php

return [
    'enabled' => true,
    'providers' => [
        'google' => [
            'enabled' => true,
            'client_id' => getenv('GOOGLE_CALENDAR_CLIENT_ID'),
            'client_secret' => getenv('GOOGLE_CALENDAR_CLIENT_SECRET'),
            'redirect_uri' => getenv('GOOGLE_CALENDAR_REDIRECT_URI')
        ],
        'outlook' => [
            'enabled' => false,
            'client_id' => getenv('OUTLOOK_CALENDAR_CLIENT_ID'),
            'client_secret' => getenv('OUTLOOK_CALENDAR_CLIENT_SECRET'),
            'redirect_uri' => getenv('OUTLOOK_CALENDAR_REDIRECT_URI')
        ]
    ],
    'defaults' => [
        'timezone' => 'UTC',
        'working_hours' => [
            'start' => '09:00',
            'end' => '17:00'
        ],
        'working_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
        'event_duration' => 60, // minutes
        'reminder_time' => 15 // minutes before event
    ],
    'sync' => [
        'enabled' => true,
        'interval' => 300, // 5 minutes
        'look_ahead' => 30, // days
        'look_behind' => 7 // days
    ],
    'caching' => [
        'enabled' => true,
        'ttl' => 3600 // 1 hour
    ],
    'conflict_resolution' => [
        'strategy' => 'ask', // 'ask', 'auto_adjust', 'reject'
        'buffer_time' => 15 // minutes
    ]
];
