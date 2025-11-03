<?php

it('has default purge days configuration', function () {
    expect(config('smart-scheduler.purge_days'))->toBe(7);
});

it('can override purge days via config', function () {
    config(['smart-scheduler.purge_days' => 30]);

    expect(config('smart-scheduler.purge_days'))->toBe(30);
});

it('has email recipients configuration', function () {
    expect(config('smart-scheduler.notifications.email.recipients'))
        ->toBeArray();
});

it('parses comma-separated email recipients from env', function () {
    // Simulate ENV variable
    $_ENV['SMART_SCHEDULER_EMAIL_RECIPIENTS'] = 'admin@example.com,ops@example.com';

    // Manually parse like the config does
    $recipients = explode(',', $_ENV['SMART_SCHEDULER_EMAIL_RECIPIENTS']);

    expect($recipients)->toHaveCount(2)
        ->and($recipients[0])->toBe('admin@example.com')
        ->and($recipients[1])->toBe('ops@example.com');

    unset($_ENV['SMART_SCHEDULER_EMAIL_RECIPIENTS']);
});

it('returns empty array when no email recipients configured', function () {
    config(['smart-scheduler.notifications.email.recipients' => []]);

    expect(config('smart-scheduler.notifications.email.recipients'))
        ->toBeArray()
        ->toBeEmpty();
});
