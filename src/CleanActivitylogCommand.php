<?php

namespace Spatie\Activitylog;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

class CleanActivitylogCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'activitylog:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old records from the activity log.';

    public function handle()
    {
        $this->comment('Cleaning activity log...');

        $maxAgeInDays = config('laravel-activitylog.delete_records_older_than_days');

        $cutOffDate = Carbon::now()->subDays($maxAgeInDays)->format('Y-m-d H:i:s');

        $amountDeleted = Activity::where('created_at', '<', $cutOffDate)->delete();

        $this->info("Deleted {$amountDeleted} record(s) from the activity log.");

        $this->comment('All done!');
    }
}
