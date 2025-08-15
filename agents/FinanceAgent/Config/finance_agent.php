<?php

return [
    'enabled' => true,
    'tools' => [
        'database',
        'gemini'
    ],
    'categories' => [
        'housing' => ['rent', 'mortgage', 'utilities', 'maintenance'],
        'transportation' => ['fuel', 'public_transport', 'car_maintenance'],
        'food' => ['groceries', 'dining_out', 'delivery'],
        'healthcare' => ['medical', 'dental', 'pharmacy'],
        'entertainment' => ['streaming', 'movies', 'games'],
        'shopping' => ['clothing', 'electronics', 'household'],
        'education' => ['tuition', 'books', 'courses'],
        'savings' => ['emergency_fund', 'investments', 'retirement'],
        'other' => ['miscellaneous', 'unknown']
    ],
    'budget_templates' => [
        '50_30_20' => [
            'essentials' => 50,
            'wants' => 30,
            'savings' => 20
        ],
        '70_20_10' => [
            'essentials' => 70,
            'savings' => 20,
            'discretionary' => 10
        ]
    ],
    'analysis' => [
        'spending_threshold_alerts' => true,
        'monthly_reports' => true,
        'trend_analysis_period' => 90, // days
        'anomaly_detection' => true
    ],
    'currency' => [
        'default' => 'USD',
        'supported' => ['USD', 'EUR', 'GBP', 'JPY']
    ]
];
