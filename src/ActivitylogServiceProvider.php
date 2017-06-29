<?php

namespace Spatie\Activitylog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Exceptions\InvalidConfiguration;

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
        
        $this->loadMigrationsFrom(__DIR__.'/../migrations');
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->app->bind('command.activitylog:clean', CleanActivitylogCommand::class);

        $this->commands([
            'command.activitylog:clean',
        ]);
    }

    public static function determineActivityModel(): string
    {
        $activityModel = config('laravel-activitylog.activity_model') ?? Activity::class;

        if (! is_a($activityModel, Activity::class, true)) {
            throw InvalidConfiguration::modelIsNotValid($activityModel);
        }

        return $activityModel;
    }

    public static function getActivityModelInstance(): Model
    {
        $activityModelClassName = self::determineActivityModel();

        return new $activityModelClassName();
    }
}
