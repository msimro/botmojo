<?php

return [
    'enabled' => true,
    'api' => [
        'key' => getenv('GEMINI_API_KEY'),
        'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent',
        'model' => 'gemini-pro',
        'version' => '1.0'
    ],
    'parameters' => [
        'temperature' => 0.7,
        'top_p' => 0.95,
        'top_k' => 40,
        'max_output_tokens' => 2048
    ],
    'safety_settings' => [
        'harassment' => 'block',
        'hate_speech' => 'block',
        'sexually_explicit' => 'block',
        'dangerous_content' => 'block'
    ],
    'rate_limiting' => [
        'requests_per_minute' => 60,
        'tokens_per_minute' => 60000,
        'retry_attempts' => 3,
        'retry_delay' => 1000 // milliseconds
    ],
    'caching' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
        'max_size' => 1000 // entries
    ],
    'logging' => [
        'enabled' => true,
        'level' => 'info',
        'include_prompts' => true,
        'include_responses' => true
    ]
];
