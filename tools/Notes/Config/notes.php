<?php

return [
    'enabled' => true,
    'storage' => [
        'type' => 'database', // 'database', 'filesystem', 's3'
        'path' => __DIR__ . '/../../../../data/notes',
        's3' => [
            'bucket' => getenv('NOTES_S3_BUCKET'),
            'region' => getenv('AWS_REGION'),
            'key' => getenv('AWS_ACCESS_KEY_ID'),
            'secret' => getenv('AWS_SECRET_ACCESS_KEY')
        ]
    ],
    'features' => [
        'categories' => true,
        'tags' => true,
        'attachments' => true,
        'versioning' => true,
        'sharing' => true,
        'encryption' => true
    ],
    'limits' => [
        'max_note_size' => 1048576, // 1MB
        'max_attachment_size' => 5242880, // 5MB
        'max_attachments_per_note' => 10,
        'max_tags_per_note' => 20
    ],
    'search' => [
        'enabled' => true,
        'engine' => 'elasticsearch', // 'elasticsearch', 'database'
        'highlight' => true,
        'fuzzy_matching' => true
    ],
    'backup' => [
        'enabled' => true,
        'frequency' => 'daily',
        'retention_days' => 30,
        'compress' => true
    ],
    'sync' => [
        'enabled' => true,
        'interval' => 300, // 5 minutes
        'conflict_resolution' => 'last_write_wins' // 'last_write_wins', 'merge'
    ]
];
