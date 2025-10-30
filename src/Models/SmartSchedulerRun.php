<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SmartSchedulerRun extends Model
{
    use HasFactory;

    protected $table = 'smart_scheduler_runs';

    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {

            if (empty($model->{$model->getKeyName()})) {
                $generator = config('smart-scheduler.id_generator', 'ulid');

                if ($generator === 'uuid') {
                    $model->{$model->getKeyName()} = (string) Str::uuid();
                } else {
                    $model->{$model->getKeyName()} = (string) Str::ulid();
                }
            }

            if (empty($model->hash)) {
                $generator = config('smart-scheduler.id_generator', 'ulid');

                if ($generator === 'uuid') {
                    $model->hash = (string) Str::uuid();
                } else {
                    $model->hash = (string) Str::ulid();
                }
            }
        });
    }

    protected $keyType = 'string';

    public $incrementing = false;
}
