<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmartSchedulerRun extends Model
{
    use HasFactory;

    protected $table = 'smart_scheduler_runs';

    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];
}
