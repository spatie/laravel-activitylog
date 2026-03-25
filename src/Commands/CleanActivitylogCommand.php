<?php

namespace Spatie\Activitylog\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Spatie\Activitylog\Support\Config;

class CleanActivitylogCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'activitylog:clean
                            {log? : (optional) The log name that will be cleaned.}
                            {--days= : (optional) Records older than this number of days will be cleaned.}
                            {--force : (optional) Force the operation to run when in production.}';

    protected $description = 'Clean up old records from the activity log.';

    public function handle(): int
    {
        if (! $this->confirmToProceed()) {
            return 1;
        }

        $this->comment('Cleaning activity log...');

        $maxAgeInDays = $this->option('days') ?? config('activitylog.clean_after_days');

        if (filter_var($maxAgeInDays, FILTER_VALIDATE_INT) === false || (int) $maxAgeInDays < 1) {
            $this->error('The days option must be a positive integer.');

            return 1;
        }

        $amountDeleted = Config::cleanActivityLogAction()->execute(
            (int) $maxAgeInDays,
            $this->argument('log'),
        );

        $this->info("Deleted {$amountDeleted} record(s) from the activity log.");

        $this->comment('All done!');

        return 0;
    }
}
