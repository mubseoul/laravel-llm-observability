<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Recording Settings
    |--------------------------------------------------------------------------
    |
    | Configure how LLM requests are recorded and stored.
    |
    */

    'recording' => [
        // Enable or disable recording globally
        'enabled' => env('LLM_RECORDING_ENABLED', true),

        // Recording mode: 'sync' or 'async' (uses queue)
        'mode' => env('LLM_RECORDING_MODE', 'async'),

        // Queue connection and name for async recording
        'queue' => [
            'connection' => env('LLM_QUEUE_CONNECTION', null),
            'name' => env('LLM_QUEUE_NAME', 'default'),
        ],

        // Sampling rate (1.0 = 100%, 0.1 = 10%)
        'sampling_rate' => env('LLM_SAMPLING_RATE', 1.0),

        // Store raw prompt and response bodies (privacy risk!)
        'store_bodies' => env('LLM_STORE_BODIES', false),

        // Redact sensitive patterns from metadata
        'redact_patterns' => [
            '/api[_-]?key["\s:=]+([a-zA-Z0-9_\-]+)/i',
            '/bearer\s+([a-zA-Z0-9_\-\.]+)/i',
            '/password["\s:=]+([^\s,"]+)/i',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pricing Configuration
    |--------------------------------------------------------------------------
    |
    | Pricing per provider and model (USD per 1M tokens).
    | Update these values as providers change their pricing.
    |
    */

    'pricing' => [
        'openai' => [
            'gpt-4' => ['input' => 30.00, 'output' => 60.00],
            'gpt-4-32k' => ['input' => 60.00, 'output' => 120.00],
            'gpt-4-turbo' => ['input' => 10.00, 'output' => 30.00],
            'gpt-4-turbo-preview' => ['input' => 10.00, 'output' => 30.00],
            'gpt-4o' => ['input' => 5.00, 'output' => 15.00],
            'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60],
            'gpt-3.5-turbo' => ['input' => 0.50, 'output' => 1.50],
            'gpt-3.5-turbo-16k' => ['input' => 3.00, 'output' => 4.00],
        ],
        'anthropic' => [
            'claude-3-opus-20240229' => ['input' => 15.00, 'output' => 75.00],
            'claude-3-sonnet-20240229' => ['input' => 3.00, 'output' => 15.00],
            'claude-3-haiku-20240307' => ['input' => 0.25, 'output' => 1.25],
            'claude-3-5-sonnet-20241022' => ['input' => 3.00, 'output' => 15.00],
            'claude-3-5-haiku-20241022' => ['input' => 1.00, 'output' => 5.00],
        ],
        'ollama' => [
            'default' => ['input' => 0.00, 'output' => 0.00],
        ],
        'other' => [
            'default' => ['input' => 1.00, 'output' => 2.00],
        ],
    ],

    // Custom pricing provider class (must implement PricingProvider contract)
    'pricing_provider' => null,

    /*
    |--------------------------------------------------------------------------
    | Token Estimation
    |--------------------------------------------------------------------------
    |
    | When token counts are not available from the API, estimate from characters.
    |
    */

    'token_estimation' => [
        // Average characters per token (GPT uses ~4 chars/token)
        'chars_per_token' => 4,

        // Custom token estimator class (must implement TokenEstimator contract)
        'estimator' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Quotas & Enforcement
    |--------------------------------------------------------------------------
    |
    | Configure quota enforcement behavior.
    |
    */

    'quotas' => [
        // Enable quota enforcement
        'enabled' => env('LLM_QUOTAS_ENABLED', true),

        // Default quotas (null = no limit)
        'defaults' => [
            'global' => [
                'requests_per_day' => null,
                'requests_per_month' => null,
                'tokens_per_day' => null,
                'tokens_per_month' => null,
                'cost_per_day' => null,
                'cost_per_month' => null,
            ],
            'user' => [
                'requests_per_day' => 1000,
                'requests_per_month' => 10000,
                'tokens_per_day' => 1000000,
                'tokens_per_month' => 10000000,
                'cost_per_day' => 10.00,
                'cost_per_month' => 100.00,
            ],
            'team' => [
                'requests_per_day' => 5000,
                'requests_per_month' => 50000,
                'tokens_per_day' => 5000000,
                'tokens_per_month' => 50000000,
                'cost_per_day' => 50.00,
                'cost_per_month' => 500.00,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alerts & Webhooks
    |--------------------------------------------------------------------------
    |
    | Configure alerting and webhook delivery.
    |
    */

    'alerts' => [
        // Enable alerts
        'enabled' => env('LLM_ALERTS_ENABLED', true),

        // Channels to send notifications (mail, slack, etc.)
        'notification_channels' => ['mail'],

        // Webhook configuration
        'webhooks' => [
            'enabled' => env('LLM_WEBHOOKS_ENABLED', true),
            'retry_times' => 3,
            'retry_delay' => 5, // seconds
            'timeout' => 10, // seconds
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    |
    | Configure how long to keep historical data.
    |
    */

    'retention' => [
        // Days to keep detailed request logs (null = keep forever)
        'requests_days' => env('LLM_RETENTION_REQUESTS', 90),

        // Days to keep usage aggregates (null = keep forever)
        'aggregates_days' => env('LLM_RETENTION_AGGREGATES', 365),

        // Days to keep webhook delivery logs
        'webhook_deliveries_days' => env('LLM_RETENTION_WEBHOOKS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    |
    | Configure the Filament admin dashboard.
    |
    */

    'dashboard' => [
        // Enable the dashboard
        'enabled' => env('LLM_DASHBOARD_ENABLED', true),

        // Dashboard route prefix
        'prefix' => env('LLM_DASHBOARD_PREFIX', 'llm-observability'),

        // Middleware for dashboard routes
        'middleware' => ['web', 'auth'],

        // Dashboard timezone
        'timezone' => env('LLM_DASHBOARD_TIMEZONE', 'UTC'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    |
    | Configure database connection and table names.
    |
    */

    'database' => [
        'connection' => env('LLM_DB_CONNECTION', null),

        'tables' => [
            'requests' => 'llm_requests',
            'aggregates' => 'llm_usage_aggregates',
            'quotas' => 'llm_quotas',
            'alert_rules' => 'llm_alert_rules',
            'webhook_deliveries' => 'llm_webhook_deliveries',
        ],
    ],
];
