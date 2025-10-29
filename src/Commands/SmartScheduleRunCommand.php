<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Commands;

use Illuminate\Console\Command;

class SmartScheduleRunCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smart-schedule:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the Laravel scheduler with SmartScheduler tracking (stub).';

    public function handle(): int
    {
        // TODO: implement wrapper logic (create run record, prevent overlaps, call scheduler, record result)
        $this->info('smart-schedule:run stub executed. Implement logic in package.');

        return 0;
    }
}
