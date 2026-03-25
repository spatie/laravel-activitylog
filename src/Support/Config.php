<?php

namespace Spatie\Activitylog\Support;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Actions\CleanActivityLogAction;
use Spatie\Activitylog\Actions\LogActivityAction;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;
use Spatie\Activitylog\Exceptions\InvalidConfiguration;
use Spatie\Activitylog\Models\Activity as ActivityModel;

class Config
{
    public static function activityModel(): string
    {
        $activityModel = config('activitylog.activity_model') ?? ActivityModel::class;

        if (! is_a($activityModel, ActivityContract::class, true)) {
            throw InvalidConfiguration::modelIsNotValid($activityModel);
        }

        if (! is_a($activityModel, Model::class, true)) {
            throw InvalidConfiguration::modelIsNotValid($activityModel);
        }

        return $activityModel;
    }

    public static function activityModelInstance(): ActivityContract
    {
        $activityModelClassName = static::activityModel();

        return new $activityModelClassName;
    }

    public static function logActivityAction(): LogActivityAction
    {
        return static::resolveAction('activitylog.actions.log_activity', LogActivityAction::class);
    }

    public static function cleanActivityLogAction(): CleanActivityLogAction
    {
        return static::resolveAction('activitylog.actions.clean_log', CleanActivityLogAction::class);
    }

    protected static function resolveAction(string $configKey, string $defaultClass): mixed
    {
        $actionClass = config($configKey) ?? $defaultClass;

        if (! is_a($actionClass, $defaultClass, true)) {
            throw InvalidConfiguration::actionIsNotValid($actionClass, $defaultClass);
        }

        return app($actionClass);
    }
}
