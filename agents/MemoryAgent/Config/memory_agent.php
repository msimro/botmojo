<?php

return [
    'enabled' => true,
    'tools' => [
        'database',
        'gemini'
    ],
    'memory_types' => [
        'general',
        'conversation',
        'task',
        'knowledge',
        'relationship'
    ],
    'ttl' => [
        'general' => 2592000,    // 30 days
        'conversation' => 604800, // 7 days
        'task' => 1209600,       // 14 days
        'knowledge' => 31536000,  // 1 year
        'relationship' => 31536000 // 1 year
    ],
    'cache' => [
        'enabled' => true,
        'driver' => 'redis',
        'ttl' => 3600 // 1 hour
    ]
];
