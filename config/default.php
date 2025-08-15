<?php

/**
 * BotMojo Default Configuration
 *
 * This file contains the default configuration values for the application.
 * Environment-specific overrides should be placed in their respective files
 * (e.g., development.php, production.php).
 */

return [
    // Database configuration
    'database' => [
        'host' => 'localhost',
        'name' => 'botmojo',
        'user' => 'botmojo',
        'password' => '',
        'charset' => 'utf8mb4',
    ],

    // Logging configuration
    'log' => [
        'path' => __DIR__ . '/../logs',
        'level' => 'debug',
    ],

    // Gemini API configuration
    'gemini' => [
        'api_key' => '',
        'model' => 'gemini-1.5-flash',
    ],

    // Tool configuration
    'tools' => [
        // Tool configurations will be merged from individual tool config files
    ],

    // Agent configuration
    'agents' => [
        // Agent configurations will be loaded dynamically
    ],

    // Memory system configuration
    'memory' => [
        'conversation_ttl' => 3600, // 1 hour
        'cache_driver' => 'redis',
    ],
];
