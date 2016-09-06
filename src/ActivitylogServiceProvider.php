<?php

namespace Spatie\Activitylog;

use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Exceptions\InvalidConfiguration;
use Spatie\Activitylog\Models\Activity;

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
            'laravel-activitylog', function($app) {
            return self::getActivityModelInstance();
        });

        $this->app->bind('command.activitylog:clean', CleanActivitylogCommand::class);

        $this->commands([
            'command.activitylog:clean',
        ]);
    }

    /**
     * @throws \Spatie\Activitylog\Exceptions\InvalidConfiguration
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    static public function determineActivityModel()
    {
        $activityModel = config('laravel-activitylog.activity_model') != null ?
            config('laravel-activitylog.activity_model') :
            Activity::class;

        if (! is_a($activityModel, Activity::class, true)) {
            throw InvalidConfiguration::modelIsNotValid($activityModel);
        }

        return $activityModel;
    }

    static public function getActivityModelInstance()
    {
        $activityModelClassName = self::determineActivityModel();

        return new $activityModelClassName();
    }
}
