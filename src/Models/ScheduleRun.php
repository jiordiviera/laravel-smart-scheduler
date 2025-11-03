<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model representing a scheduled task execution.
 *
 * @property int $id
 * @property string $task_identifier Unique task identifier
 * @property string $status Execution status (starting, success, failed, ignored)
 * @property \Carbon\Carbon $started_at Start date/time
 * @property \Carbon\Carbon|null $finished_at End date/time
 * @property float|null $duration Duration in seconds
 * @property string|null $output Console output (partial)
 * @property string|null $exception Exception message on failure
 * @property string $server_name Host server name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ScheduleRun extends Model
{
    /**
     * Possible execution statuses.
     */
    public const STATUS_STARTING = 'starting';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const STATUS_IGNORED = 'ignored';

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'smart_schedule_runs';

    /**
     * Mass assignable attributes.
     *
     * @var array<string>
     */
    protected $fillable = [
        'task_identifier',
        'status',
        'started_at',
        'finished_at',
        'duration',
        'output',
        'exception',
        'server_name',
    ];

    /**
     * Attribute casts.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'duration' => 'decimal:3',
    ];

    /**
     * Check if the execution is currently running.
     */
    public function isStarting(): bool
    {
        return $this->status === self::STATUS_STARTING;
    }

    /**
     * Check if the execution was successful.
     */
    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * Check if the execution failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if the execution was ignored.
     */
    public function isIgnored(): bool
    {
        return $this->status === self::STATUS_IGNORED;
    }
}
