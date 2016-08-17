<?php

namespace Spatie\Activitylog;

use Illuminate\Support\ServiceProvider;

class ActivitylogServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/laravel-activitylog.php' => config_path('laravel-activitylog.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__.'/../config/laravel-activitylog.php', 'laravel-activitylog');

        if (! class_exists('CreateActivityLogTable')) {
            $timestamp = date('Y_m_d_His', time());

            $this->publishes([
                __DIR__.'/../migrations/create_activity_log_table.php.stub' => database_path("/migrations/{$timestamp}_create_activity_log_table.php"),
            ], 'migrations');
        }
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->app->bind(
            'laravel-activitylog',
            \Spatie\Activitylog\ActivityLogger::class
        );

        $this->app->bind('command.activitylog:clean', CleanActivitylogCommand::class);

        $this->commands([
            'command.activitylog:clean',
        ]);
    }
}
