<?php

return [
    'enabled' => true,
    'tools' => [
        'database',
        'gemini',
        'fitness'
    ],
    'metrics' => [
        'vitals' => [
            'heart_rate',
            'blood_pressure',
            'temperature',
            'respiratory_rate'
        ],
        'fitness' => [
            'steps',
            'distance',
            'calories_burned',
            'active_minutes'
        ],
        'sleep' => [
            'duration',
            'quality',
            'cycles',
            'interruptions'
        ],
        'nutrition' => [
            'calories',
            'protein',
            'carbs',
            'fats',
            'water'
        ]
    ],
    'goals' => [
        'default_steps' => 10000,
        'default_sleep_hours' => 8,
        'default_water_intake' => 2000, // ml
        'default_active_minutes' => 30
    ],
    'analysis' => [
        'trend_period' => 30, // days
        'alert_thresholds' => [
            'high_heart_rate' => 100,
            'low_heart_rate' => 50,
            'high_blood_pressure' => '140/90',
            'low_blood_pressure' => '90/60'
        ]
    ],
    'recommendations' => [
        'enabled' => true,
        'frequency' => 'daily',
        'personalization' => true
    ]
];
