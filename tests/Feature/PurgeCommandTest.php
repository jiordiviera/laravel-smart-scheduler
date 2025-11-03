<?php

use Carbon\Carbon;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\ScheduleRun;

it('purges old successful records', function () {
    // Create old successful record (10 days ago)
    ScheduleRun::create([
        'task_identifier' => 'old-task',
        'status' => ScheduleRun::STATUS_SUCCESS,
        'started_at' => Carbon::now()->subDays(10),
        'finished_at' => Carbon::now()->subDays(10)->addMinutes(5),
        'duration' => 300,
        'server_name' => 'localhost',
        'created_at' => Carbon::now()->subDays(10),
    ]);

    // Create recent successful record (2 days ago)
    ScheduleRun::create([
        'task_identifier' => 'recent-task',
        'status' => ScheduleRun::STATUS_SUCCESS,
        'started_at' => Carbon::now()->subDays(2),
        'finished_at' => Carbon::now()->subDays(2)->addMinutes(5),
        'duration' => 300,
        'server_name' => 'localhost',
        'created_at' => Carbon::now()->subDays(2),
    ]);

    expect(ScheduleRun::count())->toBe(2);

    // Run purge command with 7 days retention
    $this->artisan('smart-scheduler:purge', ['--days' => 7])
        ->assertSuccessful();

    // Old record should be deleted, recent one should remain
    expect(ScheduleRun::count())->toBe(1)
        ->and(ScheduleRun::first()->task_identifier)->toBe('recent-task');
});

it('purges old ignored records', function () {
    // Create old ignored record
    ScheduleRun::create([
        'task_identifier' => 'old-ignored',
        'status' => ScheduleRun::STATUS_IGNORED,
        'started_at' => Carbon::now()->subDays(10),
        'finished_at' => Carbon::now()->subDays(10),
        'server_name' => 'localhost',
        'created_at' => Carbon::now()->subDays(10),
    ]);

    $this->artisan('smart-scheduler:purge', ['--days' => 7])
        ->assertSuccessful();

    expect(ScheduleRun::count())->toBe(0);
});

it('does not purge failed records', function () {
    // Create old failed record
    ScheduleRun::create([
        'task_identifier' => 'old-failed',
        'status' => ScheduleRun::STATUS_FAILED,
        'started_at' => Carbon::now()->subDays(10),
        'finished_at' => Carbon::now()->subDays(10)->addMinutes(5),
        'exception' => 'Test error',
        'server_name' => 'localhost',
        'created_at' => Carbon::now()->subDays(10),
    ]);

    $this->artisan('smart-scheduler:purge', ['--days' => 7])
        ->assertSuccessful();

    // Failed record should not be purged
    expect(ScheduleRun::count())->toBe(1)
        ->and(ScheduleRun::first()->isFailed())->toBeTrue();
});

it('does not purge starting records', function () {
    // Create old starting record (stuck task)
    ScheduleRun::create([
        'task_identifier' => 'stuck-task',
        'status' => ScheduleRun::STATUS_STARTING,
        'started_at' => Carbon::now()->subDays(10),
        'server_name' => 'localhost',
        'created_at' => Carbon::now()->subDays(10),
    ]);

    $this->artisan('smart-scheduler:purge', ['--days' => 7])
        ->assertSuccessful();

    // Starting record should not be purged
    expect(ScheduleRun::count())->toBe(1);
});

it('respects custom days parameter', function () {
    // Create records at different ages
    ScheduleRun::create([
        'task_identifier' => '20-days-old',
        'status' => ScheduleRun::STATUS_SUCCESS,
        'started_at' => Carbon::now()->subDays(20),
        'finished_at' => Carbon::now()->subDays(20),
        'server_name' => 'localhost',
        'created_at' => Carbon::now()->subDays(20),
    ]);

    ScheduleRun::create([
        'task_identifier' => '15-days-old',
        'status' => ScheduleRun::STATUS_SUCCESS,
        'started_at' => Carbon::now()->subDays(15),
        'finished_at' => Carbon::now()->subDays(15),
        'server_name' => 'localhost',
        'created_at' => Carbon::now()->subDays(15),
    ]);

    // Purge with 18 days retention
    $this->artisan('smart-scheduler:purge', ['--days' => 18])
        ->assertSuccessful();

    // Only the 20-days-old should be purged
    expect(ScheduleRun::count())->toBe(1)
        ->and(ScheduleRun::first()->task_identifier)->toBe('15-days-old');
});

it('shows correct output in dry-run mode', function () {
    ScheduleRun::create([
        'task_identifier' => 'old-task',
        'status' => ScheduleRun::STATUS_SUCCESS,
        'started_at' => Carbon::now()->subDays(10),
        'finished_at' => Carbon::now()->subDays(10),
        'server_name' => 'localhost',
        'created_at' => Carbon::now()->subDays(10),
    ]);

    $this->artisan('smart-scheduler:purge', ['--dry-run' => true])
        ->expectsOutput('DRY RUN: Would delete 1 record(s).')
        ->assertSuccessful();

    // Record should still exist
    expect(ScheduleRun::count())->toBe(1);
});

it('handles no records to purge gracefully', function () {
    $this->artisan('smart-scheduler:purge')
        ->expectsOutput('No records to purge.')
        ->assertSuccessful();
});
