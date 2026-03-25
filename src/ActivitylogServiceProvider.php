<?php

namespace Spatie\Activitylog;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Spatie\Activitylog\Support\ActivityBuffer;
use Spatie\Activitylog\Support\ActivityLogger;
use Spatie\Activitylog\Support\ActivityLogStatus;
use Spatie\Activitylog\Support\CauserResolver;
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

        $this->app->scoped(ActivityBuffer::class);
    }

    public function packageBooted(): void
    {
        if (config('activitylog.buffer.enabled', false)) {
            $this->registerActivityBufferFlushing();
        }
    }

    protected function registerActivityBufferFlushing(): void
    {
        $this->app->terminating(fn () => app(ActivityBuffer::class)->flush());

        $this->app['events']->listen(
            [JobProcessed::class, JobFailed::class],
            fn () => app(ActivityBuffer::class)->flush(),
        );

        register_shutdown_function(function () {
            try {
                app(ActivityBuffer::class)->flush();
            } catch (\Throwable) {
                // Container may be unavailable during shutdown
            }
        });
    }
}
