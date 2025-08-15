<?php

return [
    'enabled' => true,
    'tools' => [
        'database',
        'gemini',
        'calendar'
    ],
    'task_types' => [
        'todo' => [
            'priority_levels' => ['low', 'medium', 'high', 'urgent'],
            'status_options' => ['pending', 'in_progress', 'completed', 'deferred']
        ],
        'event' => [
            'types' => ['meeting', 'appointment', 'deadline', 'reminder'],
            'recurrence' => ['none', 'daily', 'weekly', 'monthly', 'yearly']
        ],
        'goal' => [
            'timeframes' => ['short_term', 'medium_term', 'long_term'],
            'categories' => ['personal', 'professional', 'health', 'financial']
        ]
    ],
    'scheduling' => [
        'working_hours' => [
            'start' => '09:00',
            'end' => '17:00'
        ],
        'time_slots' => [
            'default_duration' => 30, // minutes
            'buffer_time' => 15 // minutes
        ],
        'calendar_sync' => [
            'enabled' => true,
            'providers' => ['google', 'outlook', 'apple']
        ]
    ],
    'notifications' => [
        'enabled' => true,
        'channels' => ['email', 'push', 'in_app'],
        'advance_notice' => [
            'default' => 15, // minutes
            'important' => 60 // minutes
        ]
    ],
    'smart_suggestions' => [
        'enabled' => true,
        'features' => [
            'time_optimization',
            'task_grouping',
            'priority_adjustment'
        ]
    ]
];
