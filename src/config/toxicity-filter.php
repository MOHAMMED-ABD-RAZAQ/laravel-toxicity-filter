<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default AI provider that will be used for
    | toxicity detection. You may configure multiple providers and switch
    | between them as needed.
    |
    | Supported: "openai", "perspective", "huggingface"
    |
    */
    'default' => env('TOXICITY_FILTER_PROVIDER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | AI Providers Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure multiple AI providers for toxicity detection.
    | Each provider has its own configuration options and endpoints.
    |
    */
    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODERATION_MODEL', 'text-moderation-latest'),
            'endpoint' => 'https://api.openai.com/v1/moderations',
            'timeout' => 30,
        ],

        'perspective' => [
            'api_key' => env('PERSPECTIVE_API_KEY'),
            'endpoint' => 'https://commentanalyzer.googleapis.com/v1alpha1/comments:analyze',
            'attributes' => [
                'TOXICITY',
                'SEVERE_TOXICITY',
                'IDENTITY_ATTACK',
                'INSULT',
                'PROFANITY',
                'THREAT',
            ],
            'timeout' => 30,
        ],

        'huggingface' => [
            'api_key' => env('HUGGINGFACE_API_KEY'),
            'model' => env('HUGGINGFACE_MODEL', 'unitary/toxic-bert'),
            'endpoint' => 'https://api-inference.huggingface.co/models/',
            'timeout' => 30,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Toxicity Thresholds
    |--------------------------------------------------------------------------
    |
    | Configure the toxicity score thresholds for different actions.
    | Scores are typically between 0 and 1, where 1 is most toxic.
    |
    */
    'thresholds' => [
        'block' => env('TOXICITY_BLOCK_THRESHOLD', 0.8),
        'flag' => env('TOXICITY_FLAG_THRESHOLD', 0.6),
        'warn' => env('TOXICITY_WARN_THRESHOLD', 0.4),
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Types to Monitor
    |--------------------------------------------------------------------------
    |
    | Specify which types of content should be automatically monitored
    | for toxicity when using the middleware.
    |
    */
    'monitor_content_types' => [
        'text',
        'comments',
        'posts',
        'messages',
        'reviews',
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Actions
    |--------------------------------------------------------------------------
    |
    | Configure what actions to take when toxic content is detected.
    |
    */
    'actions' => [
        'block' => [
            'enabled' => true,
            'message' => 'Your content has been blocked due to inappropriate language.',
            'http_status' => 422,
        ],
        'flag' => [
            'enabled' => true,
            'notify_admins' => true,
            'allow_content' => false,
        ],
        'warn' => [
            'enabled' => true,
            'message' => 'Please review your content for appropriate language.',
            'allow_content' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging and Storage
    |--------------------------------------------------------------------------
    |
    | Configure how toxicity detection results should be logged and stored.
    |
    */
    'logging' => [
        'enabled' => env('TOXICITY_LOGGING_ENABLED', true),
        'store_content' => env('TOXICITY_STORE_CONTENT', false),
        'log_level' => env('TOXICITY_LOG_LEVEL', 'info'),
        'table_name' => 'toxicity_detections',
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Enable queue support for processing large content or bulk moderation
    | asynchronously to improve application performance.
    |
    */
    'queue' => [
        'enabled' => env('TOXICITY_QUEUE_ENABLED', false),
        'connection' => env('TOXICITY_QUEUE_CONNECTION', 'default'),
        'queue_name' => env('TOXICITY_QUEUE_NAME', 'toxicity-moderation'),
        'job_timeout' => env('TOXICITY_JOB_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Cache toxicity detection results to improve performance and reduce
    | API calls for identical content.
    |
    */
    'cache' => [
        'enabled' => env('TOXICITY_CACHE_ENABLED', false), // Disabled by default
        'ttl' => env('TOXICITY_CACHE_TTL', 3600), // 1 hour
        'store' => env('TOXICITY_CACHE_STORE', null), // Use default cache store
        'prefix' => 'toxicity_filter:',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting to prevent abuse and manage API quotas.
    |
    */
    'rate_limiting' => [
        'enabled' => env('TOXICITY_RATE_LIMITING_ENABLED', true),
        'requests_per_minute' => env('TOXICITY_REQUESTS_PER_MINUTE', 100),
        'requests_per_hour' => env('TOXICITY_REQUESTS_PER_HOUR', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bypass Rules
    |--------------------------------------------------------------------------
    |
    | Configure rules for bypassing toxicity detection for certain users,
    | content types, or other conditions.
    |
    */
    'bypass' => [
        'admin_users' => env('TOXICITY_BYPASS_ADMINS', true),
        'trusted_user_roles' => ['admin', 'moderator'],
        'whitelisted_domains' => [],
        'content_length_min' => 3, // Don't check very short content
    ],
];
