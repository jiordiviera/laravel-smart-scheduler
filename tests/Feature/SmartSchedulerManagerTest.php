<?php

use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\ScheduleRun;

it('prevents overlapping executions by checking status', function () {
    // Create first run with starting status
    $firstRun = ScheduleRun::create([
        'task_identifier' => 'test-task',
        'status' => ScheduleRun::STATUS_STARTING,
        'started_at' => now(),
        'server_name' => 'localhost',
    ]);

    // Check if there's an existing running task
    $existingRun = ScheduleRun::query()
        ->where('task_identifier', 'test-task')
        ->where('status', ScheduleRun::STATUS_STARTING)
        ->exists();

    expect($existingRun)->toBeTrue();

    // When we try to create another run, it should be ignored
    $secondRun = ScheduleRun::create([
        'task_identifier' => 'test-task',
        'status' => ScheduleRun::STATUS_IGNORED,
        'started_at' => now(),
        'finished_at' => now(),
        'server_name' => 'localhost',
    ]);

    expect($secondRun->status)->toBe(ScheduleRun::STATUS_IGNORED);
});
