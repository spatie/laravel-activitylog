<?php

namespace Spatie\Activitylog;

use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanActivitylogCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'activitylog:clean
                            {log? : (optional) The log name that will be cleaned.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old records from the activity log.';

    public function handle()
    {
        $this->comment('Cleaning activity log...');

        $log = $this->argument('log');

        $maxAgeInDays = config('activitylog.delete_records_older_than_days');

        $cutOffDate = Carbon::now()->subDays($maxAgeInDays)->format('Y-m-d H:i:s');

        $activity = ActivitylogServiceProvider::getActivityModelInstance();

        $amountDeleted = $activity::where('created_at', '<', $cutOffDate)
            ->when($log !== null, function ($query) use ($log) {
                $query->inLog($log);
            })
            ->delete();

        $this->info("Deleted {$amountDeleted} record(s) from the activity log.");

        $this->comment('All done!');
    }
}
