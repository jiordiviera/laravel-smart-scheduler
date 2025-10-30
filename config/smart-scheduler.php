<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Smart Scheduler Configuration
    |--------------------------------------------------------------------------
    |
    | Add package configuration options here. This is a minimal file to
    | allow publishing and merging from the service provider.
    |
    */

    'enabled' => true,
    'log' => env('SMART_SCHEDULER_LOG', false),

    /*
    |--------------------------------------------------------------------------
    | ID generator for run hashes
    |--------------------------------------------------------------------------
    | Choose between 'ulid' (sortable, compact) or 'uuid' (standard). Defaults
    | to 'ulid'. The migration stores up to 36 characters so both formats are
    | supported.
    */
    'id_generator' => env('SMART_SCHEDULER_ID_GENERATOR', 'ulid'),

    /*
    |--------------------------------------------------------------------------
    | Execution safeguards
    |--------------------------------------------------------------------------
    */
    'prevent_overlaps' => env('SMART_SCHEDULER_PREVENT_OVERLAPS', true),
    'stuck_after_minutes' => env('SMART_SCHEDULER_STUCK_AFTER_MINUTES', 15),
    'wrapped_command' => env('SMART_SCHEDULER_WRAPPED_COMMAND', 'schedule:run'),

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    | Configure how the scheduler reports anomalies (failures, stuck jobs, skips).
    | Supported channels: mail, slack, telegram. Toggle with the enabled flag
    | and list the channels you want to receive notifications on.
    */
    'notifications' => [
        'enabled' => env('SMART_SCHEDULER_NOTIFICATIONS_ENABLED', false),
        'channels' => [
            // 'mail',
            // 'slack',
            // 'telegram',
        ],
        'mail' => [
            'to' => env('SMART_SCHEDULER_MAIL_TO'),
        ],
        'slack' => [
            'webhook_url' => env('SMART_SCHEDULER_SLACK_WEBHOOK'),
        ],
        'telegram' => [
            'bot_token' => env('SMART_SCHEDULER_TELEGRAM_TOKEN'),
            'chat_id' => env('SMART_SCHEDULER_TELEGRAM_CHAT_ID'),
        ],
    ],
];
