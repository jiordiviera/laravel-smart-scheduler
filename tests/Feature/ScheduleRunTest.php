<?php

use Illuminate\Support\Facades\Schema;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\ScheduleRun;

it('creates smart_schedule_runs table', function () {
    expect(Schema::hasTable('smart_schedule_runs'))->toBeTrue();
});

it('can create a schedule run record', function () {
    $run = ScheduleRun::create([
        'task_identifier' => 'test:task',
        'status' => ScheduleRun::STATUS_STARTING,
        'started_at' => now(),
        'server_name' => 'localhost',
    ]);

    expect($run)->toBeInstanceOf(ScheduleRun::class)
        ->and($run->task_identifier)->toBe('test:task')
        ->and($run->status)->toBe(ScheduleRun::STATUS_STARTING);
});

it('has correct status helper methods', function () {
    $startingRun = ScheduleRun::create([
        'task_identifier' => 'test:task',
        'status' => ScheduleRun::STATUS_STARTING,
        'started_at' => now(),
        'server_name' => 'localhost',
    ]);

    expect($startingRun->isStarting())->toBeTrue()
        ->and($startingRun->isSuccess())->toBeFalse()
        ->and($startingRun->isFailed())->toBeFalse()
        ->and($startingRun->isIgnored())->toBeFalse();
});
