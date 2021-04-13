<?php

namespace Spatie\Activitylog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;
use Spatie\Activitylog\Exceptions\InvalidConfiguration;
use Spatie\Activitylog\Models\Activity as ActivityModel;

class ActivitylogServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->bootConfig();
        $this->bootMigrations();
    }

    public function register()
    {
        $this->app->bind('command.activitylog:clean', CleanActivitylogCommand::class);

        $this->commands([
            'command.activitylog:clean',
        ]);

        $this->app->bind(ActivityLogger::class);

        $this->app->singleton(LoggerBatch::class);

        $this->app->singleton(CauserResolver::class);

        $this->app->singleton(ActivityLogStatus::class);
    }

    public static function determineActivityModel(): string
    {
        $activityModel = config('activitylog.activity_model') ?? ActivityModel::class;

        if (! is_a($activityModel, Activity::class, true)
            || ! is_a($activityModel, Model::class, true)) {
            throw InvalidConfiguration::modelIsNotValid($activityModel);
        }

        return $activityModel;
    }

    public static function getActivityModelInstance(): ActivityContract
    {
        $activityModelClassName = self::determineActivityModel();

        return new $activityModelClassName();
    }

    protected function bootConfig(): void
    {
        $this->publishes([
            __DIR__.'/../config/activitylog.php' => config_path('activitylog.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__.'/../config/activitylog.php', 'activitylog');
    }

    protected function bootMigrations(): void
    {
        foreach ([
            'CreateActivityLogTable',
            'AddEventColumnToActivityLogTable',
            'AddBatchUuidColumnToActivityLogTable',
        ] as $i => $migration) {
            if (! class_exists($migration)) {
                $this->publishes([
                    __DIR__.'/../migrations/'.Str::snake($migration).'.php.stub' => database_path(sprintf(
                        '/migrations/%s_%s.php',
                        date('Y_m_d_His', time() + $i),
                        Str::snake($migration)
                    )),
                ], 'migrations');
            }
        }
    }
}
