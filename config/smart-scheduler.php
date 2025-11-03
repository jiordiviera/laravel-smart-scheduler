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
        | List of email addresses to notify when tasks fail.
        |
        */
        'email' => [
            'recipients' => env('SMART_SCHEDULER_EMAIL_RECIPIENTS')
                ? explode(',', env('SMART_SCHEDULER_EMAIL_RECIPIENTS'))
                : [],
        ],
    ],
];
