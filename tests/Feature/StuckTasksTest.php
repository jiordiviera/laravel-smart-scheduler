<?php

use Carbon\Carbon;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\ScheduleRun;

it('detects stuck tasks older than timeout', function () {
    // Create a task that started 2 hours ago (stuck)
    ScheduleRun::create([
        'task_identifier' => 'stuck-task',
        'status' => ScheduleRun::STATUS_STARTING,
        'started_at' => Carbon::now()->subHours(2),
        'server_name' => 'localhost',
    ]);

    // Create a task that started 30 minutes ago (not stuck with default 60 min timeout)
    ScheduleRun::create([
        'task_identifier' => 'running-task',
        'status' => ScheduleRun::STATUS_STARTING,
        'started_at' => Carbon::now()->subMinutes(30),
        'server_name' => 'localhost',
    ]);

    $this->artisan('smart-scheduler:detect-stuck')
        ->assertSuccessful();

    // Check that stuck task was marked
    $stuckTask = ScheduleRun::where('task_identifier', 'stuck-task')->first();
    expect($stuckTask->status)->toBe(ScheduleRun::STATUS_STUCK)
        ->and($stuckTask->isStuck())->toBeTrue()
        ->and($stuckTask->finished_at)->not->toBeNull()
        ->and($stuckTask->exception)->toContain('stuck');

    // Check that running task was not affected
    $runningTask = ScheduleRun::where('task_identifier', 'running-task')->first();
    expect($runningTask->status)->toBe(ScheduleRun::STATUS_STARTING);
});

it('respects custom timeout parameter', function () {
    // Create a task that started 45 minutes ago
    ScheduleRun::create([
        'task_identifier' => 'recent-task',
        'status' => ScheduleRun::STATUS_STARTING,
        'started_at' => Carbon::now()->subMinutes(45),
        'server_name' => 'localhost',
    ]);

    // With default 60 min timeout, should not be stuck
    $this->artisan('smart-scheduler:detect-stuck')
        ->assertSuccessful();

    expect(ScheduleRun::first()->status)->toBe(ScheduleRun::STATUS_STARTING);

    // With 30 min timeout, should be stuck
    $this->artisan('smart-scheduler:detect-stuck', ['--timeout' => 30])
        ->assertSuccessful();

    expect(ScheduleRun::first()->status)->toBe(ScheduleRun::STATUS_STUCK);
});

it('does not modify tasks in dry-run mode', function () {
    ScheduleRun::create([
        'task_identifier' => 'stuck-task',
        'status' => ScheduleRun::STATUS_STARTING,
        'started_at' => Carbon::now()->subHours(2),
        'server_name' => 'localhost',
    ]);

    $this->artisan('smart-scheduler:detect-stuck', ['--dry-run' => true])
        ->expectsOutput('DRY RUN: No changes made.')
        ->assertSuccessful();

    // Task should still be in starting status
    expect(ScheduleRun::first()->status)->toBe(ScheduleRun::STATUS_STARTING);
});

it('handles no stuck tasks gracefully', function () {
    $this->artisan('smart-scheduler:detect-stuck')
        ->expectsOutput('No stuck tasks detected.')
        ->assertSuccessful();
});

it('auto-detects stuck tasks when new task starts', function () {
    // Create an old stuck task
    ScheduleRun::create([
        'task_identifier' => 'my-task',
        'status' => ScheduleRun::STATUS_STARTING,
        'started_at' => Carbon::now()->subHours(2),
        'server_name' => 'localhost',
    ]);

    // Simulate a new task starting with the same identifier using mock
    $manager = app(\Jiordiviera\SmartScheduler\LaravelSmartScheduler\Services\SmartSchedulerManager::class);

    $task = Mockery::mock(\Illuminate\Console\Scheduling\Event::class);
    $task->description = 'my-task';
    $task->command = null;
    $task->expression = null;

    $event = new \Illuminate\Console\Events\ScheduledTaskStarting($task);

    $run = $manager->handleTaskStarting($event);

    // The old task should now be marked as stuck
    $oldTask = ScheduleRun::where('task_identifier', 'my-task')
        ->where('status', ScheduleRun::STATUS_STUCK)
        ->first();

    expect($oldTask)->not->toBeNull();

    // New task should be created successfully (not ignored)
    expect($run->status)->toBe(ScheduleRun::STATUS_STARTING);
});

it('still ignores concurrent tasks within timeout', function () {
    // Create a task that started 30 minutes ago (within timeout)
    ScheduleRun::create([
        'task_identifier' => 'my-task',
        'status' => ScheduleRun::STATUS_STARTING,
        'started_at' => Carbon::now()->subMinutes(30),
        'server_name' => 'localhost',
    ]);

    $manager = app(\Jiordiviera\SmartScheduler\LaravelSmartScheduler\Services\SmartSchedulerManager::class);

    $task = Mockery::mock(\Illuminate\Console\Scheduling\Event::class);
    $task->description = 'my-task';
    $task->command = null;
    $task->expression = null;

    $event = new \Illuminate\Console\Events\ScheduledTaskStarting($task);

    $run = $manager->handleTaskStarting($event);

    // New task should be ignored due to overlap
    expect($run->status)->toBe(ScheduleRun::STATUS_IGNORED);
});

it('has correct isStuck helper method', function () {
    $stuckRun = ScheduleRun::create([
        'task_identifier' => 'test:task',
        'status' => ScheduleRun::STATUS_STUCK,
        'started_at' => now()->subHours(2),
        'finished_at' => now(),
        'server_name' => 'localhost',
    ]);

    expect($stuckRun->isStuck())->toBeTrue()
        ->and($stuckRun->isStarting())->toBeFalse()
        ->and($stuckRun->isSuccess())->toBeFalse()
        ->and($stuckRun->isFailed())->toBeFalse();
});

it('does not purge stuck records by default', function () {
    // Create an old stuck record
    ScheduleRun::create([
        'task_identifier' => 'old-stuck',
        'status' => ScheduleRun::STATUS_STUCK,
        'started_at' => Carbon::now()->subDays(10),
        'finished_at' => Carbon::now()->subDays(10),
        'server_name' => 'localhost',
        'created_at' => Carbon::now()->subDays(10),
    ]);

    $this->artisan('smart-scheduler:purge', ['--days' => 7])
        ->assertSuccessful();

    // Stuck record should not be purged (like failed records)
    expect(ScheduleRun::count())->toBe(1)
        ->and(ScheduleRun::first()->isStuck())->toBeTrue();
});
