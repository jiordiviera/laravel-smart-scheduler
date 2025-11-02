<?php

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\SmartSchedulerRun;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Services\SchedulerRunOutcome;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Services\SmartSchedulerManager;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Support\SmartSchedulerNotifier;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Support\SmartSchedulerRunWatcher;

beforeEach(function () {
    // Default commands used by the manager during tests
    Artisan::command('smart-scheduler:success-run', fn () => Command::SUCCESS)
        ->purpose('Simulated successful scheduler command');

    Artisan::command('smart-scheduler:failure-run', function () {
        return Command::FAILURE;
    })->purpose('Simulated failing scheduler command');

    config()->set('smart-scheduler.notifications.enabled', false);
});

it('marks a scheduler run as success when wrapped command succeeds', function () {
    config()->set('smart-scheduler.wrapped_command', 'smart-scheduler:success-run');

    /** @var SmartSchedulerManager $manager */
    $manager = app(SmartSchedulerManager::class);

    $outcome = $manager->execute();

    expect($outcome->status())->toBe(SchedulerRunOutcome::STATUS_SUCCESS);
    expect($outcome->exitCode())->toBe(Command::SUCCESS);
    expect($outcome->run())->not->toBeNull();

    $run = $outcome->run();

    expect($run->status)->toBe(SmartSchedulerRun::STATUS_SUCCESS);
    expect($run->ended_at)->not->toBeNull();
    expect($run->duration_ms)->toBeGreaterThanOrEqual(0);
});

it('returns native outcome when smart scheduler is disabled', function () {
    config()->set('smart-scheduler.enabled', false);
    config()->set('smart-scheduler.wrapped_command', 'smart-scheduler:failure-run');

    /** @var SmartSchedulerManager $manager */
    $manager = app(SmartSchedulerManager::class);

    $outcome = $manager->execute();

    expect($outcome->status())->toBe(SchedulerRunOutcome::STATUS_NATIVE);
    expect($outcome->exitCode())->toBe(Command::FAILURE);
    expect($outcome->run())->toBeNull();
    expect(SmartSchedulerRun::count())->toBe(0);
});

it('skips execution when another run is already marked as running', function () {
    config()->set('smart-scheduler.wrapped_command', 'smart-scheduler:success-run');

    $activeRun = SmartSchedulerRun::create([
        'command' => 'smart-scheduler:success-run',
        'status' => SmartSchedulerRun::STATUS_RUNNING,
        'started_at' => now(),
    ]);

    /** @var SmartSchedulerManager $manager */
    $manager = app(SmartSchedulerManager::class);

    $outcome = $manager->execute();

    expect($outcome->status())->toBe(SchedulerRunOutcome::STATUS_SKIPPED);
    expect($outcome->exitCode())->toBe(Command::SUCCESS);
    expect($outcome->run())->not->toBeNull();

    $skippedRun = $outcome->run();

    expect($skippedRun->status)->toBe(SmartSchedulerRun::STATUS_SKIPPED);
    expect($skippedRun->error_message)->toContain($activeRun->id);

    // Original run stays untouched to allow manual intervention
    expect($activeRun->fresh()->status)->toBe(SmartSchedulerRun::STATUS_RUNNING);
});

it('marks an overdue run as stuck and triggers notifier', function () {
    config()->set('smart-scheduler.wrapped_command', 'smart-scheduler:success-run');
    config()->set('smart-scheduler.notifications.enabled', true);
    config()->set('smart-scheduler.stuck_after_minutes', 1);

    app()->forgetInstance(SmartSchedulerNotifier::class);

    $notifier = new class() extends SmartSchedulerNotifier {
        public int $calls = 0;
        public ?SmartSchedulerRun $lastRun = null;
        public ?\Throwable $lastException = null;

        public function __construct()
        {
            // Parent expects iterable; provide an empty array since we override send logic.
            parent::__construct([]);
        }

        public function notifyFailure(SmartSchedulerRun $run, ?\Throwable $exception = null): void
        {
            $this->calls++;
            $this->lastRun = $run;
            $this->lastException = $exception;

            parent::notifyFailure($run, $exception);
        }
    };

    app()->instance(SmartSchedulerNotifier::class, $notifier);

    $staleRun = SmartSchedulerRun::create([
        'command' => 'smart-scheduler:success-run',
        'status' => SmartSchedulerRun::STATUS_RUNNING,
        'started_at' => now()->subMinutes(5),
    ]);

    /** @var SmartSchedulerManager $manager */
    $manager = app(SmartSchedulerManager::class);

    $outcome = $manager->execute();

    expect($outcome->status())->toBe(SchedulerRunOutcome::STATUS_STUCK);
    expect($outcome->exitCode())->toBe(Command::FAILURE);

    $failedRun = $outcome->run();
    expect($failedRun)->not->toBeNull();
    expect($failedRun->status)->toBe(SmartSchedulerRun::STATUS_FAILED);
    expect($failedRun->error_message)->toContain('stuck');

    expect($notifier->calls)->toBe(1);
    expect($notifier->lastRun?->id)->toBe($failedRun->id);
    expect($notifier->lastException)->not->toBeNull();
});

it('fails with a helpful message when migrations are missing', function () {
    Schema::dropIfExists('smart_scheduler_runs');

    config()->set('smart-scheduler.wrapped_command', 'smart-scheduler:success-run');

    /** @var SmartSchedulerManager $manager */
    $manager = app(SmartSchedulerManager::class);

    $outcome = $manager->execute();

    expect($outcome->status())->toBe(SchedulerRunOutcome::STATUS_NATIVE);
    expect($outcome->exitCode())->toBe(Command::FAILURE);
    expect($outcome->message())->toContain('migrations');
});

it('marks run as failed when a scheduled task reports failure without throwing', function () {
    config()->set('smart-scheduler.wrapped_command', 'smart-scheduler:background-failure');

    Artisan::command('smart-scheduler:background-failure', function () {
        app(SmartSchedulerRunWatcher::class)->recordFailure(new \RuntimeException('Background failure'));
    })->purpose('simulate failure callback without throwing');

    /** @var SmartSchedulerManager $manager */
    $manager = app(SmartSchedulerManager::class);

    $outcome = $manager->execute();

    expect($outcome->status())->toBe(SchedulerRunOutcome::STATUS_FAILURE);
    expect($outcome->exitCode())->toBe(Command::FAILURE);
    expect($outcome->message())->toContain('Background failure');

    $run = SmartSchedulerRun::first();
    expect($run->status)->toBe(SmartSchedulerRun::STATUS_FAILED);
    expect($run->error_message)->toBe('Background failure');
});

it('persists runs using the configured database connection', function () {
    config()->set('database.connections.scheduler_logs', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);

    Schema::connection('scheduler_logs')->dropIfExists('smart_scheduler_runs');
    Schema::connection('scheduler_logs')->create('smart_scheduler_runs', function (Blueprint $table) {
        $table->string('id', 36)->primary();
        $table->string('command')->nullable();
        $table->string('status')->index();
        $table->timestamp('started_at')->nullable();
        $table->timestamp('ended_at')->nullable();
        $table->integer('duration_ms')->nullable();
        $table->text('error_message')->nullable();
        $table->string('hash', 36)->nullable()->index();
        $table->timestamps();
    });

    config()->set('smart-scheduler.connection', 'scheduler_logs');
    config()->set('smart-scheduler.wrapped_command', 'smart-scheduler:success-run');

    /** @var SmartSchedulerManager $manager */
    $manager = app(SmartSchedulerManager::class);
    $outcome = $manager->execute();

    expect($outcome->status())->toBe(SchedulerRunOutcome::STATUS_SUCCESS);
    expect($outcome->run())->not->toBeNull();
    expect($outcome->run()->getConnectionName())->toBe('scheduler_logs');

    expect(DB::connection('scheduler_logs')->table('smart_scheduler_runs')->count())->toBe(1);
    expect(DB::connection('testing')->table('smart_scheduler_runs')->count())->toBe(0);

    config()->set('smart-scheduler.connection', null);
});
