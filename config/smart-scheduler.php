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
];
