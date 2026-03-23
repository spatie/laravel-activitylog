<?php

namespace Spatie\Activitylog;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ActivitylogServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
        ->name('laravel-activitylog')
        ->hasConfigFile('activitylog')
        ->hasMigrations([
            'create_activity_log_table',
        ])
        ->hasCommand(Commands\CleanActivitylogCommand::class);
    }

    public function registeringPackage(): void
    {
        $this->app->bind(ActivityLogger::class);

        $this->app->scoped(CauserResolver::class);

        $this->app->scoped(ActivityLogStatus::class);
    }
}
