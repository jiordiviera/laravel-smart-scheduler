<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Smart Scheduler Purge Days
    |--------------------------------------------------------------------------
    |
    | Number of days to retain successful and ignored schedule run records.
    | Older records will be deleted when running the purge command.
    |
    */
    'purge_days' => env('SMART_SCHEDULER_PURGE_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Stuck Task Timeout
    |--------------------------------------------------------------------------
    |
    | Number of minutes after which a task in "starting" status is considered
    | stuck. This prevents tasks from blocking future executions indefinitely
    | if the server crashes or the process is killed.
    |
    */
    'stuck_timeout_minutes' => env('SMART_SCHEDULER_STUCK_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure how to notify about failed task executions.
    |
    */
    'notifications' => [
        /*
        |----------------------------------------------------------------------
        | Email Notifications
        |----------------------------------------------------------------------
        |
        | List of email addresses to notify when tasks fail or get stuck.
        |
        */
        'email' => [
            'recipients' => env('SMART_SCHEDULER_EMAIL_RECIPIENTS')
                ? explode(',', env('SMART_SCHEDULER_EMAIL_RECIPIENTS'))
                : [],
        ],

        /*
        |----------------------------------------------------------------------
        | Notify on Stuck Tasks
        |----------------------------------------------------------------------
        |
        | Whether to send notifications when tasks are detected as stuck.
        |
        */
        'notify_on_stuck' => env('SMART_SCHEDULER_NOTIFY_STUCK', true),
    ],
];
