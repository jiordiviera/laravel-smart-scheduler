<?php

use Carbon\Carbon;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\ScheduleRun;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Services\SmartSchedulerManager;

beforeEach(function () {
    $this->manager = app(SmartSchedulerManager::class);
});

it('calculates duration correctly when finishing a task', function () {
    // Create a starting run
    $startTime = Carbon::now()->subMinutes(5);
    $run = ScheduleRun::create([
        'task_identifier' => 'duration-test',
        'status' => ScheduleRun::STATUS_STARTING,
        'started_at' => $startTime,
        'server_name' => 'localhost',
    ]);

    // Update to finished
    $finishTime = Carbon::now();
    $run->update([
        'status' => ScheduleRun::STATUS_SUCCESS,
        'finished_at' => $finishTime,
        'duration' => $finishTime->diffInSeconds($startTime, true),
    ]);

    expect($run->duration)->toBeGreaterThan(290) // ~5 minutes
        ->and($run->duration)->toBeLessThan(310);
});

it('captures task identifier from description', function () {
    $run = ScheduleRun::create([
        'task_identifier' => 'backup:run',
        'status' => ScheduleRun::STATUS_STARTING,
        'started_at' => now(),
        'server_name' => 'localhost',
    ]);

    expect($run->task_identifier)->toBe('backup:run');
});

it('stores server name correctly', function () {
    $run = ScheduleRun::create([
        'task_identifier' => 'test-task',
        'status' => ScheduleRun::STATUS_STARTING,
        'started_at' => now(),
        'server_name' => gethostname(),
    ]);

    expect($run->server_name)->toBe(gethostname());
});

it('can store task output', function () {
    $output = 'Task completed successfully with output';

    $run = ScheduleRun::create([
        'task_identifier' => 'test-task',
        'status' => ScheduleRun::STATUS_SUCCESS,
        'started_at' => now()->subMinute(),
        'finished_at' => now(),
        'output' => $output,
        'server_name' => 'localhost',
    ]);

    expect($run->output)->toBe($output);
});

it('can store exception information', function () {
    $exception = 'RuntimeException: Something went wrong';

    $run = ScheduleRun::create([
        'task_identifier' => 'test-task',
        'status' => ScheduleRun::STATUS_FAILED,
        'started_at' => now()->subMinute(),
        'finished_at' => now(),
        'exception' => $exception,
        'server_name' => 'localhost',
    ]);

    expect($run->exception)->toBe($exception)
        ->and($run->isFailed())->toBeTrue();
});

it('marks successful runs correctly', function () {
    $run = ScheduleRun::create([
        'task_identifier' => 'test-task',
        'status' => ScheduleRun::STATUS_SUCCESS,
        'started_at' => now()->subMinute(),
        'finished_at' => now(),
        'duration' => 60.5,
        'server_name' => 'localhost',
    ]);

    expect($run->isSuccess())->toBeTrue()
        ->and($run->isFailed())->toBeFalse()
        ->and($run->isStarting())->toBeFalse();
});

it('prevents overlapping by checking existing starting tasks', function () {
    // Create first task
    ScheduleRun::create([
        'task_identifier' => 'overlap-test',
        'status' => ScheduleRun::STATUS_STARTING,
        'started_at' => now(),
        'server_name' => 'localhost',
    ]);

    // Check for overlap
    $hasOverlap = ScheduleRun::query()
        ->where('task_identifier', 'overlap-test')
        ->where('status', ScheduleRun::STATUS_STARTING)
        ->exists();

    expect($hasOverlap)->toBeTrue();
});

it('allows new execution after previous task completes', function () {
    // Create and complete first task
    $firstRun = ScheduleRun::create([
        'task_identifier' => 'sequential-test',
        'status' => ScheduleRun::STATUS_STARTING,
        'started_at' => now()->subMinute(),
        'server_name' => 'localhost',
    ]);

    $firstRun->update([
        'status' => ScheduleRun::STATUS_SUCCESS,
        'finished_at' => now(),
        'duration' => 60,
    ]);

    // Check no overlap exists
    $hasOverlap = ScheduleRun::query()
        ->where('task_identifier', 'sequential-test')
        ->where('status', ScheduleRun::STATUS_STARTING)
        ->exists();

    expect($hasOverlap)->toBeFalse();

    // New execution should be allowed
    $secondRun = ScheduleRun::create([
        'task_identifier' => 'sequential-test',
        'status' => ScheduleRun::STATUS_STARTING,
        'started_at' => now(),
        'server_name' => 'localhost',
    ]);

    expect($secondRun->isStarting())->toBeTrue();
});
